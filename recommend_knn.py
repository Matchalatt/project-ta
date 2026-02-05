import sys
import pandas as pd
import numpy as np
import json
import warnings
import pymysql
from scipy.sparse import csr_matrix
from sklearn.neighbors import NearestNeighbors

warnings.filterwarnings("ignore")

# =================================================================================
# 1. KONFIGURASI DATABASE
# =================================================================================
DB_CONFIG = {
    'host': '127.0.0.1', 
    'user': 'root', 
    'password': '',
    'db': 'snackjuara', 
    'charset': 'utf8mb4'
}

# =================================================================================
# 2. DATA LOADER (DARI DATABASE)
# =================================================================================
def load_data_from_db():
    connection = None
    try:
        connection = pymysql.connect(**DB_CONFIG)
        # Mengambil data jumlah pembelian (quantity)
        query = """
            SELECT o.user_id, oi.product_id, 
                   SUM(oi.quantity) as quantity 
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'paid' AND o.deleted_at IS NULL
            GROUP BY o.user_id, oi.product_id
        """
        df = pd.read_sql(query, connection)
        return pd.DataFrame() if df.empty else df
    except Exception:
        return pd.DataFrame()
    finally:
        if connection and connection.open:
            connection.close()

# =================================================================================
# 3. CORE CLASS: RECOMMENDER SYSTEM
# =================================================================================
class RecommenderSystem:
    def __init__(self, transactions_df):
        self.df = transactions_df
        # Menggunakan algoritma 'brute' untuk dataset kecil/menengah lebih stabil
        self.model = NearestNeighbors(metric='cosine', algorithm='brute')
        
        self.user_map = {} 
        self.user_inv_map = {}
        self.item_map = {}     
        self.item_inv_map = {} 
        self.matrix = None
        self.current_mode = None # TRACKING MODE SAAT INI

    def _create_matrix(self, mode='user'):
        """
        Membuat Sparse Matrix (CSR). Hanya dijalankan jika mode berubah.
        """
        # OPTIMISASI: Jika mode sudah sesuai, tidak perlu bikin ulang
        if self.matrix is not None and self.current_mode == mode:
            return

        # Konversi ke Categorical
        user_cat = self.df['user_id'].astype('category')
        item_cat = self.df['product_id'].astype('category')

        self.user_map = {code: uid for code, uid in enumerate(user_cat.cat.categories)}
        self.user_inv_map = {uid: code for code, uid in enumerate(user_cat.cat.categories)}
        
        self.item_map = {code: iid for code, iid in enumerate(item_cat.cat.categories)}
        self.item_inv_map = {iid: code for code, iid in enumerate(item_cat.cat.categories)}

        row_ind = user_cat.cat.codes
        col_ind = item_cat.cat.codes
        
        data = self.df['quantity'].values if 'quantity' in self.df.columns else np.ones(len(self.df))

        # Pembuatan Matrix
        if mode == 'user':
            self.matrix = csr_matrix((data, (row_ind, col_ind)), shape=(len(self.user_map), len(self.item_map)))
        else: # Item based: Transpose (Item x User)
            self.matrix = csr_matrix((data, (col_ind, row_ind)), shape=(len(self.item_map), len(self.user_map)))
        
        # Fit model sekalian disini
        self.model.fit(self.matrix)
        self.current_mode = mode

    def get_popular_items(self, n=5):
        if self.df.empty: return []
        return self.df['product_id'].value_counts().head(n).index.tolist()

    def recommend_user_based(self, user_id, n_recs=5):
        if user_id not in self.user_inv_map:
            return self.get_popular_items(n_recs)

        # Cek dan buat matrix jika belum ada atau beda mode
        self._create_matrix(mode='user')
        
        user_idx = self.user_inv_map[user_id]
        user_vec = self.matrix[user_idx]

        n_neighbors = min(self.matrix.shape[0], 20) # Naikkan sedikit jumlah neighbors
        distances, indices = self.model.kneighbors(user_vec, n_neighbors=n_neighbors)

        candidates = {}
        neighbor_indices = indices.flatten()[1:]
        neighbor_dists = distances.flatten()[1:]

        for i, idx in enumerate(neighbor_indices):
            weight = 1 / (neighbor_dists[i] + 1e-6) 
            neighbor_vec = self.matrix[idx]
            item_indices = neighbor_vec.indices 
            
            for item_idx in item_indices:
                real_item_id = self.item_map[item_idx]
                
                # OPSIONAL: Filter barang yang sudah pernah dibeli user ini?
                # Untuk snack (repeat order), filter ini BISA DIHAPUS.
                # candidates[real_item_id] = candidates.get(real_item_id, 0) + weight
                candidates[real_item_id] = candidates.get(real_item_id, 0) + weight

        rec_items = sorted(candidates.items(), key=lambda x: x[1], reverse=True)
        final_ids = [pid for pid, score in rec_items[:n_recs]]

        # Fallback ke popular items jika hasil kurang
        if len(final_ids) < n_recs:
            popular = self.get_popular_items(n_recs)
            for pid in popular:
                if pid not in final_ids:
                    final_ids.append(pid)
                    if len(final_ids) >= n_recs: break
        
        return final_ids

    def recommend_item_based(self, cart_item_ids, n_recs=5):
        valid_cart = [pid for pid in cart_item_ids if pid in self.item_inv_map]
        
        if not valid_cart:
            return self.get_popular_items(n_recs)

        # Cek dan buat matrix (Item-User)
        self._create_matrix(mode='item') 

        candidates = {}
        for pid in valid_cart:
            item_idx = self.item_inv_map[pid]
            item_vec = self.matrix[item_idx]

            n_neighbors = min(self.matrix.shape[0], n_recs + 2)
            distances, indices = self.model.kneighbors(item_vec, n_neighbors=n_neighbors)

            neighbor_indices = indices.flatten()[1:]
            neighbor_dists = distances.flatten()[1:]

            for i, idx in enumerate(neighbor_indices):
                rec_pid = self.item_map[idx]
                weight = 1 / (neighbor_dists[i] + 1e-6)

                # FILTERING PENTING: Jangan rekomendasikan barang yang ada di input
                if rec_pid not in cart_item_ids:
                    candidates[rec_pid] = candidates.get(rec_pid, 0) + weight

        rec_items = sorted(candidates.items(), key=lambda x: x[1], reverse=True)
        return [pid for pid, score in rec_items[:n_recs]]

# =================================================================================
# 4. WRAPPER FUNCTIONS (OPTIMIZED)
# =================================================================================

# Global variable untuk caching instance (agar tidak reload DB terus menerus di loop)
_recsys_instance = None

def get_recsys_instance(df=None):
    global _recsys_instance
    # Jika df disediakan, kita paksa update instance
    if df is not None:
        if _recsys_instance is None or not _recsys_instance.df.equals(df):
            _recsys_instance = RecommenderSystem(df)
    # Jika instance belum ada dan df tidak ada, load dari DB
    elif _recsys_instance is None:
        df_db = load_data_from_db()
        _recsys_instance = RecommenderSystem(df_db)
    
    return _recsys_instance

def get_recommendations_for_user(user_id, transactions_df=None, num_recommendations=5):
    # Gunakan instance global atau buat baru dengan df yang dikirim
    recsys = get_recsys_instance(transactions_df)
    if recsys.df.empty: return []
    return recsys.recommend_user_based(user_id, n_recs=num_recommendations)

def get_recommendations_for_cart(cart_ids, transactions_df=None, num_recommendations=5):
    recsys = get_recsys_instance(transactions_df)
    if recsys.df.empty: return []
    return recsys.recommend_item_based(cart_ids, n_recs=num_recommendations)

# =================================================================================
# 5. MAIN ENTRY POINT
# =================================================================================
# ... (Bagian Main tetap sama, tidak perlu diubah)
if __name__ == "__main__":
    try:
        if len(sys.argv) < 3:
            print(json.dumps([]))
            sys.exit()

        mode = sys.argv[1]       
        input_data = sys.argv[2] 

        # Load sekali
        recsys = get_recsys_instance()
        results = []

        if not recsys.df.empty:
            if mode == "user":
                try:
                    uid = int(input_data)
                    results = recsys.recommend_user_based(uid)
                except ValueError: results = []
            
            elif mode == "item":
                try:
                    c_ids = [int(x) for x in input_data.split(',') if x.strip().isdigit()]
                    results = recsys.recommend_item_based(c_ids)
                except ValueError: results = []

        print(json.dumps(results))

    except Exception:
        print(json.dumps([]))