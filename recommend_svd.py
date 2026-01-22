import sys
import pandas as pd
import numpy as np
import json
import warnings
from scipy.sparse.linalg import svds

# Mengabaikan peringatan runtime yang mungkin muncul dari SVD
warnings.filterwarnings("ignore", category=RuntimeWarning)

def get_recommendations_for_cart(cart_product_ids, transactions_df, num_recommendations=5):
    """
    [MODE: item] Logika Item-Based Collaborative Filtering menggunakan SVD.
    Fungsi ini sekarang menerima DataFrame dan tidak terhubung ke DB.

    Args:
        cart_product_ids (list): Daftar ID produk di keranjang.
        transactions_df (pd.DataFrame): DataFrame berisi data transaksi (user_id, product_id).
        num_recommendations (int): Jumlah rekomendasi yang diinginkan.

    Returns:
        list: Daftar ID produk yang direkomendasikan.
    """
    if transactions_df.empty:
        return []

    # Membuat matriks interaksi user-item
    user_item_matrix = transactions_df.groupby(['user_id', 'product_id']).size().unstack(fill_value=0)
    user_item_matrix[user_item_matrix > 0] = 1
    
    # Memfilter ID produk di keranjang yang ada dalam histori transaksi
    valid_cart_ids = [pid for pid in cart_product_ids if pid in user_item_matrix.columns]
    if not valid_cart_ids:
        return []

    R = user_item_matrix.values
    user_ratings_mean = np.mean(R, axis=1)
    R_demeaned = R - user_ratings_mean.reshape(-1, 1)
    
    # k (jumlah faktor laten) harus lebih kecil dari jumlah produk
    k = min(20, R.shape[1] - 2)
    if k <= 0: return []
    
    try:
        # Vt.T akan menjadi matriks fitur item (item latent features)
        _, _, Vt = svds(R_demeaned, k=k)
    except Exception:
        return [] # Gagal konvergensi
    
    # Membuat DataFrame untuk fitur item agar mudah dicari
    item_features = pd.DataFrame(Vt.T, index=user_item_matrix.columns)
    recommendations = {}

    # Hitung kemiripan untuk setiap item di keranjang
    for product_id in valid_cart_ids:
        target_features = item_features.loc[product_id]
        # Hitung dot product untuk mendapatkan skor kemiripan
        sim_scores = item_features.dot(target_features).sort_values(ascending=False)
        for item_id, score in sim_scores.items():
            if item_id not in cart_product_ids:
                recommendations.setdefault(item_id, 0)
                recommendations[item_id] += score # Akumulasi skor
    
    sorted_recommendations = sorted(recommendations.items(), key=lambda x: x[1], reverse=True)
    
    # Konversi ke int standar Python
    return [int(rec[0]) for rec in sorted_recommendations[:num_recommendations]]


def get_recommendations_for_user(target_user_id, transactions_df, num_recommendations=5):
    """
    [MODE: user] Logika User-Based Collaborative Filtering menggunakan SVD.
    Fungsi ini sekarang menerima DataFrame dan tidak terhubung ke DB.
    
    Args:
        target_user_id (int): ID user target.
        transactions_df (pd.DataFrame): DataFrame berisi data transaksi (user_id, product_id).
        num_recommendations (int): Jumlah rekomendasi yang diinginkan.

    Returns:
        list: Daftar ID produk yang direkomendasikan.
    """
    if transactions_df.empty:
        return []

    user_item_matrix = transactions_df.groupby(['user_id', 'product_id']).size().unstack(fill_value=0)
    user_id_map = {id_val: i for i, id_val in enumerate(user_item_matrix.index)}
    
    # Cek jika user target ada di data (bukan user baru/cold start)
    if target_user_id not in user_id_map:
        return []

    R = user_item_matrix.values
    user_ratings_mean = np.mean(R, axis=1)
    R_demeaned = R - user_ratings_mean.reshape(-1, 1)

    # k harus lebih kecil dari jumlah produk
    k = min(20, R.shape[1] - 2)
    if k <= 0: return []
    
    try:
        U, sigma, Vt = svds(R_demeaned, k=k)
    except Exception:
        return [] # Gagal konvergensi

    sigma_diag_matrix = np.diag(sigma)
    
    # Prediksi skor semua item untuk user target
    target_user_index = user_id_map[target_user_id]
    predicted_ratings = np.dot(np.dot(U[target_user_index, :], sigma_diag_matrix), Vt) + user_ratings_mean[target_user_index]
    
    predictions_df = pd.DataFrame(predicted_ratings, index=user_item_matrix.columns, columns=['predictions']).sort_values('predictions', ascending=False)
    
    # Filter produk yang sudah pernah dibeli user (berdasarkan data yang diberikan)
    user_history = user_item_matrix.iloc[target_user_index]
    unpurchased_items = user_history[user_history == 0].index
    
    recommendations = predictions_df.loc[unpurchased_items]
    
    # Konversi ke int standar Python
    return [int(pid) for pid in recommendations.head(num_recommendations).index]


if __name__ == "__main__":
    # Blok ini hanya berjalan jika file dieksekusi langsung.
    # Bertanggung jawab untuk koneksi DB dan memanggil fungsi inti.
    import pymysql

    DB_CONFIG = {
        'host': '127.0.0.1', 'user': 'root', 'password': '',
        'db': 'snackjuara', 'charset': 'utf8mb4'
    }

    def get_transaction_df_from_db():
        """Mengambil seluruh data transaksi dari database."""
        try:
            connection = pymysql.connect(**DB_CONFIG)
            query = """
                SELECT o.user_id, oi.product_id
                FROM orders o JOIN order_items oi ON o.id = oi.order_id
                WHERE o.status = 'paid' AND o.deleted_at IS NULL
            """
            return pd.read_sql(query, connection)
        finally:
            if 'connection' in locals() and connection.open:
                connection.close()

    try:
        mode = sys.argv[1]
        input_data = sys.argv[2]
        
        # Ambil SEMUA data saat dijalankan langsung (mode produksi/testing manual)
        all_transactions_df = get_transaction_df_from_db()
        results = None

        if mode == "item":
            cart_ids = [int(id_str) for id_str in input_data.split(',')]
            results = get_recommendations_for_cart(cart_ids, all_transactions_df)
        
        elif mode == "user":
            target_user_id = int(input_data)
            results = get_recommendations_for_user(target_user_id, all_transactions_df)
            
        else:
            print(json.dumps({"error": "Invalid mode specified. Use 'item' or 'user'."}))

        if results is not None:
             print(json.dumps(results))

    except (IndexError, ValueError) as e:
        print(json.dumps({"error": f"Invalid arguments provided. Error: {e}"}))
    except Exception as e:
        print(json.dumps({"error": f"An unexpected error occurred: {e}"}))