import sys
import pandas as pd
import numpy as np
import json
import os
import warnings
from scipy.sparse.linalg import svds

# Suppress warnings untuk output bersih
warnings.filterwarnings("ignore")

# =================================================================================
# FUNGSI UTAMA: MENDAPATKAN REKOMENDASI (User-Based & Item-Based)
# =================================================================================

def get_recommendations_for_user(user_id, transactions_df=None, num_recommendations=5):
    """
    [MODE: user] Matrix Factorization menggunakan SVD (Singular Value Decomposition).
    Memprediksi 'skor' user terhadap semua item, lalu mengambil yang tertinggi.
    """
    if transactions_df is None:
        transactions_df = get_data_from_source()

    if transactions_df.empty: return []

    # 1. Buat Pivot Table (User x Product)
    # Gunakan 'quantity' atau 'count' sebagai nilai implicit rating
    user_item_matrix = transactions_df.groupby(['user_id', 'product_id'])['quantity'].sum().unstack(fill_value=0)

    # 2. Cek User Existing
    if user_id not in user_item_matrix.index:
        return [] # User tidak dikenal (Cold Start)

    # 3. Konversi ke Matrix Float
    R = user_item_matrix.values.astype(float)
    
    # Normalisasi (kurangi rata-rata user) - Optional untuk implicit feedback, tapi bagus untuk SVD
    user_ratings_mean = np.mean(R, axis=1)
    R_demeaned = R - user_ratings_mean.reshape(-1, 1)

    # 4. Tentukan nilai k (Latent Factors)
    # k tidak boleh lebih besar dari jumlah user atau item minus 1
    n_users, n_items = R.shape
    k = min(50, min(n_users, n_items) - 1)
    
    if k < 1: return [] # Data terlalu sedikit untuk SVD

    # 5. Hitung SVD
    # U = User features, Vt = Item features
    U, sigma, Vt = svds(R_demeaned, k=k)
    sigma = np.diag(sigma)

    # 6. Prediksi Rating untuk User Target
    # Reconstruct matrix hanya untuk user ini
    # all_user_predicted_ratings = np.dot(np.dot(U, sigma), Vt) + user_ratings_mean.reshape(-1, 1)
    
    # Optimasi: Hitung hanya baris milik user target
    user_idx = user_item_matrix.index.get_loc(user_id)
    user_pred = np.dot(np.dot(U[user_idx, :], sigma), Vt) + user_ratings_mean[user_idx]
    
    # 7. Buat DataFrame Hasil Prediksi
    preds_df = pd.DataFrame(user_pred, index=user_item_matrix.columns, columns=['score'])
    
    # 8. Filter dan Sort
    # PENTING: Untuk Makanan, kita TIDAK memfilter barang yang sudah dibeli.
    # User mungkin ingin beli lagi (Repeat Order).
    
    top_recommendations = preds_df.sort_values(by='score', ascending=False).head(num_recommendations)
    
    return top_recommendations.index.tolist()

def get_recommendations_for_cart(cart_product_ids, transactions_df=None, num_recommendations=5):
    """
    [MODE: item] Menggunakan kemiripan Item-Feature dari hasil SVD.
    """
    if transactions_df is None:
        transactions_df = get_data_from_source()

    if transactions_df.empty: return []

    # Pivot Table
    user_item_matrix = transactions_df.groupby(['user_id', 'product_id']).size().unstack(fill_value=0)
    
    valid_cart_ids = [pid for pid in cart_product_ids if pid in user_item_matrix.columns]
    if not valid_cart_ids: return []

    R = user_item_matrix.values.astype(float)
    n_users, n_items = R.shape
    k = min(50, min(n_users, n_items) - 1)
    if k < 1: return []

    # SVD Decomposition
    # Kita butuh Vt (Item Features Matrix)
    U, sigma, Vt = svds(R, k=k)
    
    # Vt berukuran (k, n_items). Transpose jadi (n_items, k)
    item_factors = Vt.T 
    
    # Hitung Cosine Similarity antar Item di Latent Space
    # (Lebih efisien daripada hitung manual satu-satu)
    norms = np.linalg.norm(item_factors, axis=1, keepdims=True)
    norms[norms == 0] = 1e-10 # Hindari bagi nol
    normalized_items = item_factors / norms
    
    # Similarity Matrix (Item x Item)
    item_similarity = np.dot(normalized_items, normalized_items.T)
    item_similarity_df = pd.DataFrame(item_similarity, index=user_item_matrix.columns, columns=user_item_matrix.columns)

    # Ambil item yang mirip dengan item di keranjang
    scores = {}
    for pid in valid_cart_ids:
        if pid in item_similarity_df.index:
            # Ambil item paling mirip dengan pid ini
            sim_items = item_similarity_df[pid].sort_values(ascending=False)
            
            # Ambil top N (skip index 0 karena itu dirinya sendiri)
            for sim_pid, score in sim_items.iloc[1:num_recommendations+2].items():
                if sim_pid not in valid_cart_ids:
                    # Akumulasi skor jika item direkomendasikan oleh lebih dari 1 barang cart
                    scores[sim_pid] = scores.get(sim_pid, 0) + score

    # Sort final
    sorted_recs = sorted(scores.items(), key=lambda x: x[1], reverse=True)
    return [pid for pid, score in sorted_recs[:num_recommendations]]

# =================================================================================
# FUNGSI BANTUAN
# =================================================================================

def get_data_from_source():
    # Sama seperti KNN, prioritas ke file smart dummy
    files = [
        ('orders_dummy_smart.csv', 'order_items_dummy_smart.csv'),
        ('orders_updated.csv', 'order_items_updated.csv')
    ]

    for f_ord, f_item in files:
        if os.path.exists(f_ord) and os.path.exists(f_item):
            try:
                orders = pd.read_csv(f_ord)
                items = pd.read_csv(f_item)
                if 'status' in orders.columns:
                    orders = orders[orders['status'] == 'paid']
                
                df = pd.merge(orders, items, left_on='id', right_on='order_id')
                if 'quantity' not in df.columns: df['quantity'] = 1
                return df[['user_id', 'product_id', 'quantity']]
            except Exception:
                continue
    return pd.DataFrame()

# =================================================================================
# MAIN EXECUTION
# =================================================================================

if __name__ == "__main__":
    try:
        if len(sys.argv) < 3:
            print(json.dumps([]))
            sys.exit()

        mode = sys.argv[1]
        input_data = sys.argv[2]

        all_transactions_df = get_data_from_source()
        results = []

        if mode == "item":
            cart_ids = [int(x) for x in input_data.split(',')]
            results = get_recommendations_for_cart(cart_ids, all_transactions_df)
        
        elif mode == "user":
            target_user_id = int(input_data)
            results = get_recommendations_for_user(target_user_id, all_transactions_df)

        print(json.dumps(results))

    except Exception as e:
        print(json.dumps([]))