import sys
import pandas as pd
import numpy as np
import json
import os
import warnings
from scipy.sparse import csr_matrix
from sklearn.neighbors import NearestNeighbors

# Suppress warning agar output JSON bersih
warnings.filterwarnings("ignore")

# =================================================================================
# GLOBAL CONFIG
# =================================================================================
# File database (sesuaikan prioritas file Anda)
DATA_FILES = [
    ('orders.csv', 'order_items.csv'),
    ('orders_dummy_smart.csv', 'order_items_dummy_smart.csv')
]

# =================================================================================
# CORE CLASS: RECOMMENDER SYSTEM
# =================================================================================
class RecommenderSystem:
    def __init__(self, transactions_df):
        self.df = transactions_df
        self.model = NearestNeighbors(metric='cosine', algorithm='brute')
        self.user_map = {}      # Mapping UserID -> Matrix Index
        self.user_inv_map = {}  # Mapping Matrix Index -> UserID
        self.item_map = {}      # Mapping ItemID -> Matrix Index
        self.item_inv_map = {}  # Mapping Matrix Index -> ItemID
        self.matrix = None

    def _create_matrix(self, mode='user'):
        """
        Membuat Sparse Matrix (CSR) yang hemat memori.
        mode='user': Baris=User, Kolom=Item (Untuk User-Based CF)
        mode='item': Baris=Item, Kolom=User (Untuk Item-Based CF)
        """
        # Konversi ke Categorical untuk mapping otomatis yang efisien
        user_cat = self.df['user_id'].astype('category')
        item_cat = self.df['product_id'].astype('category')

        # Simpan mapping agar kita bisa kembalikan Index ke ID asli
        self.user_map = {code: uid for code, uid in enumerate(user_cat.cat.categories)}
        self.user_inv_map = {uid: code for code, uid in user_cat.cat.categories.items()}
        
        self.item_map = {code: iid for code, iid in enumerate(item_cat.cat.categories)}
        self.item_inv_map = {iid: code for code, iid in item_cat.cat.categories.items()}

        # Buat koordinat matriks
        row_ind = user_cat.cat.codes
        col_ind = item_cat.cat.codes
        data = self.df['quantity'].values

        # Bentuk CSR Matrix
        # Shape: (Jumlah User Unik, Jumlah Item Unik)
        if mode == 'user':
            self.matrix = csr_matrix((data, (row_ind, col_ind)), shape=(len(self.user_map), len(self.item_map)))
        else:
            # Transpose untuk Item-Based: Baris=Item, Kolom=User
            self.matrix = csr_matrix((data, (col_ind, row_ind)), shape=(len(self.item_map), len(self.user_map)))

    def get_popular_items(self, n=5):
        """Fallback: Mengembalikan item paling laris berdasarkan total quantity."""
        if self.df.empty: return []
        popular = self.df.groupby('product_id')['quantity'].sum()
        return popular.sort_values(ascending=False).head(n).index.tolist()

    def recommend_user_based(self, user_id, n_recs=5):
        """User-Based Collaborative Filtering"""
        # 1. Cek User
        if user_id not in self.user_inv_map:
            return self.get_popular_items(n_recs)

        # 2. Persiapan Matriks (Lazy Loading)
        if self.matrix is None:
            self._create_matrix(mode='user')
        
        # 3. Fit Model
        self.model.fit(self.matrix)

        # 4. Cari Tetangga
        user_idx = self.user_inv_map[user_id]
        user_vec = self.matrix[user_idx]

        n_neighbors = min(self.matrix.shape[0], 11)
        distances, indices = self.model.kneighbors(user_vec, n_neighbors=n_neighbors)

        # 5. Hitung Skor Rekomendasi
        candidates = {}
        
        # Skip indeks pertama karena itu adalah user itu sendiri
        neighbor_indices = indices.flatten()[1:]
        neighbor_dists = distances.flatten()[1:]

        for i, idx in enumerate(neighbor_indices):
            weight = 1 / (neighbor_dists[i] + 1e-6) # Weighted score
            
            # Ambil item yang dibeli tetangga ini
            # Karena ini sparse matrix, kita akses row secara efisien
            neighbor_vec = self.matrix[idx]
            # indices dari nonzero elements adalah Item Index
            item_indices = neighbor_vec.indices 
            
            for item_idx in item_indices:
                real_item_id = self.item_map[item_idx]
                candidates[real_item_id] = candidates.get(real_item_id, 0) + weight

        # 6. Sort & Finalize
        rec_items = sorted(candidates.items(), key=lambda x: x[1], reverse=True)
        final_ids = [pid for pid, score in rec_items[:n_recs]]

        # Fallback jika kurang
        if len(final_ids) < n_recs:
            popular = self.get_popular_items(n_recs)
            for pid in popular:
                if pid not in final_ids:
                    final_ids.append(pid)
                    if len(final_ids) >= n_recs: break
        
        return final_ids

    def recommend_item_based(self, cart_item_ids, n_recs=5):
        """Item-Based Collaborative Filtering"""
        valid_cart = [pid for pid in cart_item_ids if pid in self.item_inv_map]
        
        if not valid_cart:
            return self.get_popular_items(n_recs)

        # 2. Persiapan Matriks (Lazy Loading) - Mode Item (Transpose)
        # Note: Kita buat object baru atau reset matrix jika perlu, 
        # tapi untuk efisiensi di script CLI, kita asumsikan mode dipanggil sekali.
        self._create_matrix(mode='item') 
        self.model.fit(self.matrix)

        candidates = {}

        for pid in valid_cart:
            item_idx = self.item_inv_map[pid]
            item_vec = self.matrix[item_idx]

            n_neighbors = min(self.matrix.shape[0], n_recs + 1)
            distances, indices = self.model.kneighbors(item_vec, n_neighbors=n_neighbors)

            neighbor_indices = indices.flatten()[1:]
            neighbor_dists = distances.flatten()[1:]

            for i, idx in enumerate(neighbor_indices):
                rec_pid = self.item_map[idx]
                weight = 1 / (neighbor_dists[i] + 1e-6)

                # Jangan rekomendasikan barang yang sudah ada di cart
                if rec_pid not in cart_item_ids:
                    candidates[rec_pid] = candidates.get(rec_pid, 0) + weight

        rec_items = sorted(candidates.items(), key=lambda x: x[1], reverse=True)
        return [pid for pid, score in rec_items[:n_recs]]

# =================================================================================
# HELPER FUNCTIONS
# =================================================================================

def load_data():
    """Load data transaksi dari CSV."""
    for f_ord, f_item in DATA_FILES:
        if os.path.exists(f_ord) and os.path.exists(f_item):
            try:
                orders = pd.read_csv(f_ord)
                items = pd.read_csv(f_item)
                
                # Filter hanya yang paid
                if 'status' in orders.columns:
                    orders = orders[orders['status'] == 'paid']
                
                df = pd.merge(orders, items, left_on='id', right_on='order_id')
                
                if 'quantity' not in df.columns:
                    df['quantity'] = 1
                
                # Pastikan tipe data benar
                df['quantity'] = pd.to_numeric(df['quantity'], errors='coerce').fillna(1)
                
                return df[['user_id', 'product_id', 'quantity']]
            except Exception:
                continue
    return pd.DataFrame()

# Wrapper Functions (Agar kompatibel dengan script evaluasi lama)
def get_recommendations_for_user(user_id, transactions_df=None, num_recommendations=5):
    if transactions_df is None: transactions_df = load_data()
    if transactions_df.empty: return []
    
    recsys = RecommenderSystem(transactions_df)
    # Kita perlu trigger _create_matrix manual jika dipanggil dari luar class
    recsys._create_matrix(mode='user') 
    return recsys.recommend_user_based(user_id, n_recs=num_recommendations)

def get_recommendations_for_cart(cart_ids, transactions_df=None, num_recommendations=5):
    if transactions_df is None: transactions_df = load_data()
    if transactions_df.empty: return []
    
    recsys = RecommenderSystem(transactions_df)
    recsys._create_matrix(mode='item')
    return recsys.recommend_item_based(cart_ids, n_recs=num_recommendations)

# =================================================================================
# MAIN ENTRY POINT (CLI / PHP)
# =================================================================================

if __name__ == "__main__":
    try:
        # Debugging: Uncomment line dibawah untuk print error ke terminal (stderr)
        # sys.stderr.write("Starting recommender...\n")

        if len(sys.argv) < 3:
            print(json.dumps([]))
            sys.exit()

        mode = sys.argv[1]       # 'user' or 'item'
        input_data = sys.argv[2] # user_id or list of item_ids

        df = load_data()
        results = []

        if not df.empty:
            recsys = RecommenderSystem(df)
            
            if mode == "user":
                try:
                    uid = int(input_data)
                    # Init matrix user
                    recsys._create_matrix(mode='user')
                    results = recsys.recommend_user_based(uid)
                except ValueError:
                    results = []
            
            elif mode == "item":
                try:
                    c_ids = [int(x) for x in input_data.split(',') if x.strip().isdigit()]
                    # Init matrix item
                    recsys._create_matrix(mode='item')
                    results = recsys.recommend_item_based(c_ids)
                except ValueError:
                    results = []

        print(json.dumps(results))

    except Exception as e:
        # Print error ke stderr agar tidak merusak format JSON stdout
        # sys.stderr.write(f"Error: {str(e)}\n")
        print(json.dumps([]))