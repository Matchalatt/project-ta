import sys
import pandas as pd
import numpy as np
import json
from sklearn.neighbors import NearestNeighbors

def get_recommendations_for_cart(cart_product_ids, transactions_df, num_recommendations=5):
    """
    [MODE: item] Logika Item-Based Collaborative Filtering menggunakan k-NN.
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

    # Buat matriks user-item, ubah jumlah pembelian menjadi biner (0 atau 1)
    user_item_matrix = transactions_df.groupby(['user_id', 'product_id']).size().unstack(fill_value=0)
    user_item_matrix[user_item_matrix > 0] = 1

    # Cek produk di keranjang yang ada di histori transaksi
    valid_cart_ids = [pid for pid in cart_product_ids if pid in user_item_matrix.columns]
    if not valid_cart_ids:
        return []

    # Transpose matriks untuk mendapatkan item-user matrix (item sebagai baris)
    item_user_matrix = user_item_matrix.T
    
    # Pastikan ada cukup item untuk membangun model
    if len(item_user_matrix) < 2:
        return []

    # Inisialisasi model k-NN untuk mencari item yang mirip
    model_knn = NearestNeighbors(metric='cosine', algorithm='brute')
    model_knn.fit(item_user_matrix.values)

    recommendations = {}
    # Cari tetangga untuk setiap item di keranjang
    for product_id in valid_cart_ids:
        product_idx = item_user_matrix.index.get_loc(product_id)
        product_vector = item_user_matrix.iloc[product_idx].values.reshape(1, -1)
        
        # Cari tetangga sebanyak n_recommendations + item di keranjang
        n_neighbors = min(len(item_user_matrix), num_recommendations + len(valid_cart_ids))
        distances, indices = model_knn.kneighbors(product_vector, n_neighbors=n_neighbors)
        
        for i in range(1, len(indices.flatten())):
            neighbor_product_id = item_user_matrix.index[indices.flatten()[i]]
            if neighbor_product_id not in cart_product_ids:
                score = 1 - distances.flatten()[i] # Skor berbanding terbalik dengan jarak
                recommendations.setdefault(neighbor_product_id, 0)
                recommendations[neighbor_product_id] += score
    
    sorted_recommendations = sorted(recommendations.items(), key=lambda x: x[1], reverse=True)
    
    # Konversi ID produk menjadi int standar Python
    return [int(rec[0]) for rec in sorted_recommendations[:num_recommendations]]


def get_recommendations_for_user(target_user_id, transactions_df, num_recommendations=5):
    """
    [MODE: user] Logika User-Based Collaborative Filtering menggunakan k-NN.
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
    user_item_matrix[user_item_matrix > 0] = 1
    
    user_id_map = {id_val: i for i, id_val in enumerate(user_item_matrix.index)}
    
    # Cek jika user target ada di data
    if target_user_id not in user_id_map:
        return []
    
    # Pastikan ada cukup tetangga untuk dicari
    n_neighbors = min(11, len(user_item_matrix.index))
    if n_neighbors <= 1:
        return []

    model_knn = NearestNeighbors(metric='cosine', algorithm='brute')
    model_knn.fit(user_item_matrix.values)

    target_user_index = user_id_map[target_user_id]
    target_user_vector = user_item_matrix.iloc[target_user_index].values.reshape(1, -1)
    
    distances, indices = model_knn.kneighbors(target_user_vector, n_neighbors=n_neighbors)
    
    neighbor_indices = indices.flatten()[1:]
    
    recommendations = {}
    for idx in neighbor_indices:
        neighbor_purchases = user_item_matrix.iloc[idx]
        for product_id, purchased in neighbor_purchases.items():
            if purchased > 0:
                recommendations.setdefault(product_id, 0)
                recommendations[product_id] += 1
    
    user_history = user_item_matrix.iloc[target_user_index]
    purchased_items = user_history[user_history > 0].index.tolist()
    
    for item in purchased_items:
        if item in recommendations:
            del recommendations[item]

    sorted_recommendations = sorted(recommendations.items(), key=lambda x: x[1], reverse=True)
    
    # Konversi ID produk menjadi int standar Python
    return [int(rec[0]) for rec in sorted_recommendations[:num_recommendations]]


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
        print(json.dumps({"error": f"An unexpected error occurred: {str(e)}"}))