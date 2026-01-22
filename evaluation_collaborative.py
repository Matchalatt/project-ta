# File: evaluate_collaborative.py
import pandas as pd
import numpy as np
import pymysql
from tqdm import tqdm
import warnings

# Mengabaikan peringatan yang tidak relevan selama eksekusi
warnings.filterwarnings("ignore", category=UserWarning)
warnings.filterwarnings("ignore", category=FutureWarning)

# =====================================================================
# 1. IMPOR FUNGSI REKOMENDASI (HANYA COLLABORATIVE)
# =====================================================================
try:
    from recommend_svd import get_recommendations_for_user as get_svd_recs
    from recommend_knn import get_recommendations_for_user as get_knn_recs
except ImportError as e:
    print(f"Gagal mengimpor modul: {e}")
    print("Pastikan file recommend_svd.py dan recommend_knn.py ada.")
    exit()

# =====================================================================
# 2. KONFIGURASI & KONEKSI DATABASE
# =====================================================================
DB_CONFIG = {
    'host': '127.0.0.1', 'user': 'root', 'password': '',
    'db': 'snackjuara', 'charset': 'utf8mb4'
}

def get_transaction_df():
    """Mengambil seluruh data transaksi dari database."""
    try:
        connection = pymysql.connect(**DB_CONFIG)
        query = """
            SELECT o.user_id, oi.product_id
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'paid' AND o.deleted_at IS NULL
        """
        df = pd.read_sql(query, connection)
        if len(df) == 0:
            print("Peringatan: Tidak ada data transaksi yang ditemukan.")
            return pd.DataFrame()
        print(f"Berhasil memuat {len(df)} baris data transaksi.")
        return df
    except Exception as e:
        print(f"Koneksi database gagal: {e}")
        return pd.DataFrame()
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

# =====================================================================
# 3. FUNGSI UNTUK MENGHITUNG METRIK EVALUASI
# =====================================================================
K = 5 # Kita akan menghitung metrik untuk top-5 rekomendasi

def precision_at_k(recommended_items, relevant_items, k=K):
    if not recommended_items or k == 0: return 0.0
    recommended_k = recommended_items[:k]
    hits = len(set(recommended_k) & set(relevant_items))
    return hits / k

def recall_at_k(recommended_items, relevant_items, k=K):
    if not relevant_items or not recommended_items: return 0.0
    recommended_k = recommended_items[:k]
    hits = len(set(recommended_k) & set(relevant_items))
    return hits / len(relevant_items)

def average_precision(recommended_items, relevant_items):
    if not relevant_items or not recommended_items: return 0.0
    score, num_hits = 0.0, 0.0
    for i, p in enumerate(recommended_items):
        if p in relevant_items:
            num_hits += 1.0
            score += num_hits / (i + 1.0)
    if not relevant_items: return 0.0
    return score / len(relevant_items)

# =====================================================================
# 4. PROSES EVALUASI UTAMA
# =====================================================================
def main():
    print("Memulai proses evaluasi model COLLABORATIVE FILTERING...")
    df = get_transaction_df()
    if df.empty:
        return

    # --- Train-Test Split (80% latih, 20% uji) ---
    train_df = df.sample(frac=0.8, random_state=42)
    test_df = df.drop(train_df.index)
    print(f"Data dibagi menjadi: {len(train_df)} latih, {len(test_df)} uji.")

    test_ground_truth = test_df.groupby('user_id')['product_id'].apply(list).to_dict()

    model_scores = {
        'SVD': {'precision': [], 'recall': [], 'ap': []},
        'k-NN': {'precision': [], 'recall': [], 'ap': []},
    }

    print("\nMengevaluasi setiap user di data uji...")
    for user_id, relevant_items in tqdm(test_ground_truth.items()):
        
        # --- Evaluasi Model Collaborative Filtering dengan train_df ---
        svd_recs = get_svd_recs(user_id, train_df, num_recommendations=K)
        knn_recs = get_knn_recs(user_id, train_df, num_recommendations=K)
        
        model_scores['SVD']['precision'].append(precision_at_k(svd_recs, relevant_items))
        model_scores['SVD']['recall'].append(recall_at_k(svd_recs, relevant_items))
        model_scores['SVD']['ap'].append(average_precision(svd_recs, relevant_items))

        model_scores['k-NN']['precision'].append(precision_at_k(knn_recs, relevant_items))
        model_scores['k-NN']['recall'].append(recall_at_k(knn_recs, relevant_items))
        model_scores['k-NN']['ap'].append(average_precision(knn_recs, relevant_items))

    # --- Agregasi dan Tampilkan Hasil Akhir ---
    final_results = {}
    for model_name, scores in model_scores.items():
        avg_precision = np.mean(scores['precision']) if scores['precision'] else 0
        avg_recall = np.mean(scores['recall']) if scores['recall'] else 0
        mean_ap = np.mean(scores['ap']) if scores['ap'] else 0 # MAP
        final_results[model_name] = {
            f'Precision@{K}': avg_precision,
            f'Recall@{K}': avg_recall,
            'MAP': mean_ap
        }

    print("\n\n--- HASIL EVALUASI COLLABORATIVE FILTERING (METRIK OFFLINE) ---")
    results_df = pd.DataFrame(final_results).T
    sorted_results_df = results_df.sort_values(by='MAP', ascending=False)
    print(sorted_results_df.round(4))
    print("---------------------------------------------------------------")
    print(f"\nKeterangan:")
    print(f" - Precision@{K}: Dari 5 item yang direkomendasikan, berapa persen yang relevan.")
    print(f" - Recall@{K}: Dari semua item relevan, berapa persen yang berhasil direkomendasikan.")
    print(f" - MAP: Metrik paling komprehensif. Skor tertinggi lebih baik.")


if __name__ == "__main__":
    main()