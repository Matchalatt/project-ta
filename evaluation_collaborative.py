# File: evaluation_collaborative_with_cm.py
# (FINAL VERSION: Combined User-Based & Item-Based Evaluation with Visual Confusion Matrix)

import pandas as pd
import numpy as np
import pymysql
import warnings
import sys
import math
from tqdm import tqdm
import matplotlib
# Gunakan backend 'Agg' untuk menyimpan file gambar tanpa menampilkan GUI
matplotlib.use('Agg') 
import matplotlib.pyplot as plt
import seaborn as sns
import os

# Mengabaikan peringatan agar output bersih
warnings.filterwarnings("ignore")

# =====================================================================
# 1. KONFIGURASI
# =====================================================================
DB_CONFIG = {
    'host': '127.0.0.1', 
    'user': 'root', 
    'password': '',
    'db': 'snackjuara', 
    'charset': 'utf8mb4'
}

K_VALUES = [5, 10]          # Top-K rekomendasi yang akan diuji (Dikurangi agar demo lebih cepat)
MIN_USER_TRX = 5            # Minimal transaksi user agar dianggap valid
MIN_ITEM_PURCHASE = 5       # Minimal pembelian produk agar dianggap valid
OUTPUT_DIR = 'eval_results' # Folder untuk menyimpan gambar confusion matrix

# Buat folder output jika belum ada
os.makedirs(OUTPUT_DIR, exist_ok=True)

# =====================================================================
# 2. IMPOR FUNGSI ALGORITMA
# =====================================================================
try:
    # Mengimpor kedua fungsi (User & Item/Cart)
    # Pastikan file recommend_knn.py ada di folder yang sama
    from recommend_knn import get_recommendations_for_user, get_recommendations_for_cart
except ImportError as e:
    print(f" Gagal mengimpor modul: {e}")
    print("Pastikan file 'recommend_knn.py' berada di folder yang sama.")
    sys.exit()

# =====================================================================
# 3. METRIK EVALUASI KOMPREHENSIF & PLOTTING
# =====================================================================

def calculate_metrics_single_row(recommended, actual, k, total_catalog_items):
    """
    Menghitung semua metrik dan komponen Confusion Matrix untuk satu user/transaksi.
    """
    # Potong rekomendasi sesuai K
    rec_k = recommended[:k]
    rec_set = set(rec_k)
    actual_set = set(actual)
    
    # --- Hitung Irisan (Basic) ---
    hits = len(rec_set & actual_set)
    
    # --- A. Confusion Matrix Components (Raw Counts) ---
    # TP: Direkomendasikan & Dibeli (Relevan & Retrieved)
    tp = hits 
    # FP: Direkomendasikan & TIDAK Dibeli (Tidak Relevan & Retrieved)
    fp = len(rec_set) - tp
    # FN: TIDAK Direkomendasikan & Dibeli (Relevan & Not Retrieved)
    fn = len(actual_set) - tp
    # TN: TIDAK Direkomendasikan & TIDAK Dibeli (Sisa katalog) (Tidak Relevan & Not Retrieved)
    tn = total_catalog_items - (tp + fp + fn)

    # --- B. Derived Metrics ---
    # 1. Precision
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0.0
    
    # 2. Recall
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0.0
    
    # 3. F1 Score
    f1 = 2 * (precision * recall) / (precision + recall) if (precision + recall) > 0 else 0.0
    
    # 4. Hit Rate (1 jika minimal ada 1 barang benar)
    hit_rate = 1.0 if hits > 0 else 0.0

    # 5. Accuracy (Skala Katalog Penuh)
    accuracy = (tp + tn) / total_catalog_items if total_catalog_items > 0 else 0.0

    # 6. NDCG
    dcg = 0.0
    for i, item in enumerate(rec_k):
        if item in actual_set:
            dcg += 1.0 / math.log2(i + 2)
    
    idcg = 0.0
    for i in range(min(len(actual_set), k)):
        idcg += 1.0 / math.log2(i + 2)
    
    ndcg = dcg / idcg if idcg > 0 else 0.0

    return {
        # Derived Metrics
        'p': precision, 'r': recall, 'f1': f1, 'hr': hit_rate, 'ndcg': ndcg, 'acc': accuracy,
        # Raw CM Components
        'tp': tp, 'fp': fp, 'fn': fn, 'tn': tn
    }

def plot_confusion_matrix(cm_total, scenario_name, k_val):
    """
    Membuat dan menyimpan plot Confusion Matrix gabungan menggunakan Seaborn.
    CM ini merepresentasikan total aggregat dari SEMUA user yang dites.
    """
    # Menyusun array 2x2 untuk Confusion Matrix
    # Format standar: [[TN, FP], [FN, TP]] agar sesuai label sumbu
    cm_array = np.array([
        [cm_total['tn'], cm_total['fp']],
        [cm_total['fn'], cm_total['tp']]
    ])

    # Label untuk anotasi (menambahkan nama kuadran dan nilainya)
    group_names = ['True Neg','False Pos','False Neg','True Pos']
    group_counts = ["{0:0.0f}".format(value) for value in cm_array.flatten()]
    # Menghitung persentase relatif terhadap total semua prediksi
    group_percentages = ["{0:.2%}".format(value) for value in cm_array.flatten()/np.sum(cm_array)]
    
    labels = [f"{v1}\n{v2}\n{v3}" for v1, v2, v3 in zip(group_names, group_counts, group_percentages)]
    labels = np.asarray(labels).reshape(2,2)

    plt.figure(figsize=(8, 6))
    sns.set(font_scale=1.1)
    
    # Plot Heatmap
    ax = sns.heatmap(cm_array, annot=labels, fmt='', cmap='Blues', cbar=False, linewidths=1, linecolor='black')
    
    # Atur Label Sumbu
    ax.set_xlabel('Predicted Condition (Rekomendasi Sistem)', fontsize=12, labelpad=10)
    ax.set_ylabel('Actual Condition (Kenyataan User)', fontsize=12, labelpad=10)
    
    ax.xaxis.set_ticklabels(['Not Recommended', 'Recommended'])
    ax.yaxis.set_ticklabels(['Not Bought', 'Bought'])

    title = f"Aggregated Confusion Matrix\nScenario: {scenario_name} @ Top-{k_val}\n(Total Catalog Scale)"
    plt.title(title, fontsize=14, pad=20)
    plt.tight_layout()

    # Simpan Gambar
    filename = f"{scenario_name.lower().replace(' ', '_').split('_(')[0]}_k{k_val}_cm.png"
    filepath = os.path.join(OUTPUT_DIR, filename)
    plt.savefig(filepath, dpi=150)
    plt.close() # Tutup plot agar tidak menumpuk di memori
    print(f"   -> Gambar CM disimpan: {filepath}")

# =====================================================================
# 4. PENGAMBILAN & PERSIAPAN DATA
# =====================================================================
def get_and_clean_data():
    print(f"\n[INIT] Menghubungkan ke Database '{DB_CONFIG['db']}'...")
    connection = None
    try:
        connection = pymysql.connect(**DB_CONFIG)
        query = """
            SELECT o.user_id, oi.product_id, o.created_at
            FROM orders o
            JOIN order_items oi ON o.id = oi.order_id
            WHERE o.status = 'paid' AND o.deleted_at IS NULL
        """
        df = pd.read_sql(query, connection)
        
        if df.empty:
            print(" Tidak ada data transaksi.")
            return pd.DataFrame()

        # Konversi Tipe Data
        df['user_id'] = pd.to_numeric(df['user_id'], errors='coerce').fillna(0).astype(int)
        df['product_id'] = pd.to_numeric(df['product_id'], errors='coerce').fillna(0).astype(int)
        
        # Urutkan berdasarkan waktu
        df['created_at'] = pd.to_datetime(df['created_at'])
        df = df.sort_values('created_at')
        
        # --- DENOISING ---
        item_counts = df['product_id'].value_counts()
        valid_items = item_counts[item_counts >= MIN_ITEM_PURCHASE].index
        df = df[df['product_id'].isin(valid_items)]

        user_counts = df['user_id'].value_counts()
        valid_users = user_counts[user_counts >= MIN_USER_TRX].index
        df = df[df['user_id'].isin(valid_users)]

        return df

    except Exception as e:
        print(f" Error Database: {e}")
        return pd.DataFrame()
    finally:
        if connection and connection.open:
            connection.close()

# =====================================================================
# 5. SKENARIO 1: EVALUASI USER-BASED
# =====================================================================
def evaluate_user_based(test_dict, train_df, total_items):
    print(f"\n [SKENARIO 1] USER-BASED CF (Personalisasi Profil)")
    
    # Struktur Data Baru: Menyimpan list metrik DAN total kumulatif CM
    results = {
        k: {
            'metrics_lists': {'p': [], 'r': [], 'f1': [], 'hr': [], 'ndcg': [], 'acc': []},
            'cm_total': {'tp': 0, 'fp': 0, 'fn': 0, 'tn': 0}
        } for k in K_VALUES
    }

    for user_id, actual_items in tqdm(test_dict.items(), desc="   User Progress"):
        try:
            recs = get_recommendations_for_user(user_id, train_df, num_recommendations=max(K_VALUES))
        except:
            recs = []

        for k in K_VALUES:
            # Dapatkan metrik dan komponen CM mentah
            res_single = calculate_metrics_single_row(recs, actual_items, k, total_items)
            
            # Simpan metrik ke list untuk dirata-rata nanti
            for key in results[k]['metrics_lists']:
                results[k]['metrics_lists'][key].append(res_single[key])
                
            # Akumulasi komponen Confusion Matrix
            for key in results[k]['cm_total']:
                results[k]['cm_total'][key] += res_single[key]
    
    return results

# =====================================================================
# 6. SKENARIO 2: EVALUASI ITEM-BASED
# =====================================================================
def evaluate_item_based(test_dict, train_df, total_items):
    print(f"\n [SKENARIO 2] ITEM-BASED CF (Rekomendasi Keranjang)")
    
    # Struktur Data Baru sama seperti User-Based
    results = {
        k: {
            'metrics_lists': {'p': [], 'r': [], 'f1': [], 'hr': [], 'ndcg': [], 'acc': []},
            'cm_total': {'tp': 0, 'fp': 0, 'fn': 0, 'tn': 0}
        } for k in K_VALUES
    }
    skipped_users = 0

    for user_id, items in tqdm(test_dict.items(), desc="   Item Progress"):
        if len(items) < 2:
            skipped_users += 1
            continue
        
        # --- SIMULASI SPLIT (MASKING) ---
        split_point = int(len(items) * 0.5)
        if split_point < 1: split_point = 1
        
        input_cart = items[:split_point]
        target_future = items[split_point:]

        try:
            recs = get_recommendations_for_cart(input_cart, train_df, num_recommendations=max(K_VALUES))
        except:
            recs = []

        for k in K_VALUES:
            res_single = calculate_metrics_single_row(recs, target_future, k, total_items)
            
            # Simpan metrik dan akumulasi CM
            for key in results[k]['metrics_lists']:
                results[k]['metrics_lists'][key].append(res_single[key])
            for key in results[k]['cm_total']:
                results[k]['cm_total'][key] += res_single[key]

    print(f"    Dilewati {skipped_users} user (belanjaan < 2 item).")
    return results

# =====================================================================
# 7. PRINT LAPORAN TEKS
# =====================================================================
def print_final_report(title, results):
    print(f"\n HASIL: {title}")
    header = f"{'K':<5} | {'Prec':<7} | {'Rec':<7} | {'F1':<7} | {'HitRate':<7} | {'NDCG':<7} | {'Accuracy':<8}"
    print(header)
    print("-" * len(header))

    for k in K_VALUES:
        # Mengambil rata-rata dari metrics_lists
        metrics = results[k]['metrics_lists']
        p = np.mean(metrics['p'])
        r = np.mean(metrics['r'])
        f = np.mean(metrics['f1'])
        h = np.mean(metrics['hr'])
        n = np.mean(metrics['ndcg'])
        a = np.mean(metrics['acc'])
        
        print(f"Top-{k:<1} | {p:.4f}  | {r:.4f}  | {f:.4f}  | {h:.4f}    | {n:.4f}  | {a:.4f}")
    print("-" * len(header))

# =====================================================================
# 8. MAIN ENTRY POINT
# =====================================================================
if __name__ == "__main__":
    print("--- MULAI EVALUASI SISTEM REKOMENDASI + VISUALISASI CM ---")
    print(f"Output gambar akan disimpan di folder: /{OUTPUT_DIR}/\n")

    # 1. Load Data
    df = get_and_clean_data()
    if df.empty: sys.exit()

    TOTAL_CATALOG_ITEMS = df['product_id'].nunique()
    print(f" Total Produk Unik di Katalog: {TOTAL_CATALOG_ITEMS}")
    print(f" Total Transaksi Valid: {len(df)}")

    # 2. Split Data (Train / Test)
    split_idx = int(len(df) * 0.7)
    train_df = df.iloc[:split_idx]
    test_df = df.iloc[split_idx:]

    # Cold Start handling
    test_df = test_df[test_df['user_id'].isin(train_df['user_id'])]
    test_df = test_df[test_df['product_id'].isin(train_df['product_id'])]

    if test_df.empty:
        print(" Data test kosong setelah filtering.")
        sys.exit()

    # Ground Truth
    test_ground_truth = test_df.groupby('user_id')['product_id'].apply(lambda x: list(set(x))).to_dict()
    print(f"  Jumlah User untuk Diuji: {len(test_ground_truth)}")

    # 3. Jalankan Evaluasi
    # Skenario A: User Based
    res_user = evaluate_user_based(test_ground_truth, train_df, TOTAL_CATALOG_ITEMS)
    
    # Skenario B: Item Based
    res_item = evaluate_item_based(test_ground_truth, train_df, TOTAL_CATALOG_ITEMS)

    # 4. Tampilkan Laporan Teks
    print("\n" + "="*60)
    print("LAPORAN TEKS PERFORMA ALGORITMA")
    print("="*60)
    print_final_report("USER-BASED CF (Personalisasi Beranda)", res_user)
    print_final_report("ITEM-BASED CF (Rekomendasi Keranjang)", res_item)

    # 5. Generate dan Simpan Plot Confusion Matrix
    print("\n" + "="*60)
    print("MEMBUAT VISUALISASI CONFUSION MATRIX (Aggregated)")
    print("="*60)
    
    print(" [User-Based Plots]")
    for k in K_VALUES:
        plot_confusion_matrix(res_user[k]['cm_total'], "User-Based CF", k)
        
    print("\n [Item-Based Plots]")
    for k in K_VALUES:
        plot_confusion_matrix(res_item[k]['cm_total'], "Item-Based CF", k)

    print(f"\nSelesai. Cek folder '{OUTPUT_DIR}' untuk melihat gambar matriks.")