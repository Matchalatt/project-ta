# File: recommend_knn.py (Versi Perbaikan)
import sys
import pandas as pd
import numpy as np
import pymysql
import json
# Impor pustaka untuk k-NN
from sklearn.neighbors import NearestNeighbors

# --- KONFIGURASI DATABASE ---
DB_CONFIG = {
    'host': '127.0.0.1',
    'user': 'root',
    'password': '',
    'db': 'snackjuara',
    'charset': 'utf8mb4'
}

def get_db_connection():
    """Membuka koneksi ke database."""
    return pymysql.connect(**DB_CONFIG)

def get_transaction_df(connection):
    """Mengambil data transaksi dan mengubahnya menjadi DataFrame."""
    query = """
        SELECT o.user_id, oi.product_id
        FROM orders o
        JOIN order_items oi ON o.id = oi.order_id
        WHERE o.status = 'paid' AND o.deleted_at IS NULL
    """
    # Mengabaikan peringatan spesifik dari pandas mengenai SQLAlchemy
    # Ini aman dilakukan dalam konteks ini
    with connection.cursor() as cursor:
        cursor.execute(query)
        result = cursor.fetchall()
        columns = [desc[0] for desc in cursor.description]
    return pd.DataFrame(result, columns=columns)

# =====================================================================
# BAGIAN ALGORITMA K-NN (MEMORY-BASED)
# =====================================================================

def get_recommendations_for_cart(cart_product_ids, num_recommendations=5):
    """
    [MODE: item] - Logika Item-Based Collaborative Filtering menggunakan k-NN.
    Mencari produk yang "berteman" dengan yang ada di keranjang.
    """
    connection = get_db_connection()
    try:
        df = get_transaction_df(connection)
        if df.empty:
            return []

        # Buat matriks user-item, ubah jumlah pembelian menjadi biner (0 atau 1)
        user_item_matrix = df.groupby(['user_id', 'product_id']).size().unstack(fill_value=0)
        user_item_matrix[user_item_matrix > 0] = 1

        # Cek produk di keranjang yang ada di histori transaksi
        valid_cart_ids = [pid for pid in cart_product_ids if pid in user_item_matrix.columns]
        if not valid_cart_ids:
            return []

        # Transpose matriks untuk mendapatkan item-user matrix (item sebagai baris)
        item_user_matrix = user_item_matrix.T
        
        # Inisialisasi model k-NN untuk mencari item yang mirip
        model_knn = NearestNeighbors(metric='cosine', algorithm='brute')
        model_knn.fit(item_user_matrix.values)

        recommendations = {}
        # Cari tetangga untuk setiap item di keranjang
        for product_id in valid_cart_ids:
            # Dapatkan indeks dan vektor dari produk saat ini
            product_idx = item_user_matrix.index.get_loc(product_id)
            product_vector = item_user_matrix.iloc[product_idx].values.reshape(1, -1)
            
            # Cari tetangga terdekat (k + item di keranjang, untuk jaga-jaga)
            distances, indices = model_knn.kneighbors(product_vector, n_neighbors=num_recommendations + len(valid_cart_ids))
            
            # Iterasi melalui tetangga, abaikan yang pertama (karena itu diri sendiri)
            for i in range(1, len(indices.flatten())):
                neighbor_idx = indices.flatten()[i]
                neighbor_product_id = item_user_matrix.index[neighbor_idx]
                
                # Hanya tambahkan jika belum ada di keranjang
                if neighbor_product_id not in cart_product_ids:
                    # Semakin kecil jarak (distance), semakin mirip, jadi skornya adalah 1 - distance
                    score = 1 - distances.flatten()[i]
                    recommendations.setdefault(neighbor_product_id, 0)
                    recommendations[neighbor_product_id] += score
        
        # Urutkan rekomendasi berdasarkan total skor kemiripan
        sorted_recommendations = sorted(recommendations.items(), key=lambda x: x[1], reverse=True)
        
        # --- PERBAIKAN DI SINI ---
        # Konversi setiap ID produk dari numpy.int64 menjadi int standar Python
        return [int(rec[0]) for rec in sorted_recommendations[:num_recommendations]]

    finally:
        if 'connection' in locals() and connection.open:
            connection.close()


def get_recommendations_for_user(target_user_id, num_recommendations=5):
    """
    [MODE: user] - Logika User-Based Collaborative Filtering menggunakan k-NN.
    Mencari user yang mirip dan merekomendasikan item yang belum dibeli.
    """
    connection = get_db_connection()
    try:
        df = get_transaction_df(connection)
        if df.empty:
            return []

        user_item_matrix = df.groupby(['user_id', 'product_id']).size().unstack(fill_value=0)
        user_item_matrix[user_item_matrix > 0] = 1 # Binerisasi: 0 jika tidak beli, 1 jika pernah beli
        
        # Buat pemetaan dari ID user asli ke indeks baris di matriks
        user_id_map = {id_val: i for i, id_val in enumerate(user_item_matrix.index)}
        
        # Cek jika user target ada di data (bukan user baru)
        if target_user_id not in user_id_map:
            return []

        # Inisialisasi model k-NN
        model_knn = NearestNeighbors(metric='cosine', algorithm='brute')
        model_knn.fit(user_item_matrix.values)

        # Dapatkan indeks dan vektor dari user target
        target_user_index = user_id_map[target_user_id]
        target_user_vector = user_item_matrix.iloc[target_user_index].values.reshape(1, -1)
        
        # Cari 11 tetangga terdekat (1 untuk diri sendiri, 10 lainnya sebagai sumber rekomendasi)
        distances, indices = model_knn.kneighbors(target_user_vector, n_neighbors=11)
        
        # Ambil indeks para tetangga (abaikan indeks pertama karena itu adalah diri sendiri)
        neighbor_indices = indices.flatten()[1:]
        
        # Kumpulkan semua produk yang pernah dibeli oleh para tetangga
        recommendations = {}
        for idx in neighbor_indices:
            neighbor_purchases = user_item_matrix.iloc[idx]
            # Iterasi produk yang dibeli tetangga
            for product_id, purchased in neighbor_purchases.items():
                if purchased > 0:
                    recommendations.setdefault(product_id, 0)
                    recommendations[product_id] += 1 # Tambah skor +1 untuk setiap kemunculan
        
        # Filter produk yang sudah pernah dibeli oleh user target
        user_history = user_item_matrix.iloc[target_user_index]
        purchased_items = user_history[user_history > 0].index.tolist()
        
        for item in purchased_items:
            if item in recommendations:
                del recommendations[item]

        # Urutkan rekomendasi berdasarkan seberapa sering produk itu dibeli oleh para tetangga
        sorted_recommendations = sorted(recommendations.items(), key=lambda x: x[1], reverse=True)
        
        # --- PERBAIKAN DI SINI ---
        # Konversi setiap ID produk dari numpy.int64 menjadi int standar Python
        return [int(rec[0]) for rec in sorted_recommendations[:num_recommendations]]

    finally:
        if 'connection' in locals() and connection.open:
            connection.close()


if __name__ == "__main__":
    try:
        mode = sys.argv[1]
        input_data = sys.argv[2]
        
        results = []
        if mode == "item":
            cart_ids = [int(id_str) for id_str in input_data.split(',')]
            results = get_recommendations_for_cart(cart_ids)
        
        elif mode == "user":
            target_user_id = int(input_data)
            results = get_recommendations_for_user(target_user_id)
            
        else:
            print(json.dumps({"error": "Invalid mode specified. Use 'item' or 'user'."}))
            sys.exit(1)
        
        print(json.dumps(results))

    except (IndexError, ValueError) as e:
        print(json.dumps({"error": f"Invalid arguments provided. Error: {e}"}))
    except Exception as e:
        # Mengubah e menjadi string untuk memastikan serialisasi JSON berhasil
        print(json.dumps({"error": f"An unexpected error occurred: {str(e)}"}))