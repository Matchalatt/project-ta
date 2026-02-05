import pandas as pd
import numpy as np
import pymysql
import warnings
import matplotlib.pyplot as plt
import seaborn as sns
from tqdm import tqdm
from recommend_tfidf import get_recommendations, _recommender_instance, ContentRecommender

# Abaikan warning agar output bersih
warnings.filterwarnings("ignore")

# =====================================================================
# 1. KONFIGURASI DATABASE
# =====================================================================
DB_CONFIG = {
    'host': '127.0.0.1', 
    'user': 'root', 
    'password': '', 
    'db': 'snackjuara', 
    'charset': 'utf8mb4'
}

# =====================================================================
# 2. FUNGSI HELPER
# =====================================================================
def get_products_with_categories():
    """Mengambil data produk dari database."""
    connection = None
    try:
        connection = pymysql.connect(**DB_CONFIG)
        query = "SELECT id, name, category, tags FROM products"
        df = pd.read_sql(query, connection)
        return df
    except Exception as e:
        print(f"Error Database: {e}")
        return pd.DataFrame()
    finally:
        if connection and connection.open:
            connection.close()

def parse_tags(tag_string):
    """Membersihkan dan memisahkan tags menjadi set."""
    if not isinstance(tag_string, str) or not tag_string:
        return set()
    return set(t.strip().lower() for t in tag_string.split(','))

def calculate_ndcg(relevant_binary, k):
    """
    Menghitung NDCG (Normalized Discounted Cumulative Gain).
    Mengukur kualitas urutan rekomendasi.
    """
    dcg = 0
    idcg = 0
    
    # 1. Hitung DCG (Discounted Cumulative Gain)
    for i in range(len(relevant_binary)):
        rel = relevant_binary[i]
        # Rank dimulai dari index 0, jadi pembagi logaritma adalah i+2
        dcg += rel / np.log2(i + 2) 
        
    # 2. Hitung IDCG (Ideal DCG - jika semua item relevan ada di urutan teratas)
    sorted_relevance = sorted(relevant_binary, reverse=True)
    for i in range(len(sorted_relevance)):
        rel = sorted_relevance[i]
        idcg += rel / np.log2(i + 2)
        
    return dcg / idcg if idcg > 0 else 0

def plot_confusion_matrix(tp, fp, fn, tn):
    """
    Membuat dan menyimpan gambar Heatmap Confusion Matrix.
    """
    # Menyusun data array untuk plotting
    # Baris 1: Actual Relevant (TP, FN)
    # Baris 2: Actual Not Relevant (FP, TN)
    cm_data = np.array([
        [tp, fn],
        [fp, tn]
    ])
    
    # Label teks untuk setiap kotak
    group_names = ['True Pos (TP)', 'False Neg (FN)', 'False Pos (FP)', 'True Neg (TN)']
    group_counts = ["{0:0.2f}".format(value) for value in cm_data.flatten()]
    
    # Menggabungkan nama label dan nilainya
    labels = [f"{v1}\n{v2}" for v1, v2 in zip(group_names, group_counts)]
    labels = np.asarray(labels).reshape(2, 2)
    
    # Setup Plotting
    plt.figure(figsize=(8, 6))
    sns.set_context('notebook', font_scale=1.2)
    
    # Membuat Heatmap
    ax = sns.heatmap(cm_data, annot=labels, fmt='', cmap='Blues', cbar=False,
                     xticklabels=['Direkomendasikan (Top-K)', 'Tidak Direkomendasikan'],
                     yticklabels=['Sebenarnya Relevan', 'Tidak Relevan'])
    
    plt.title('Rata-rata Confusion Matrix (per Query)', fontsize=16, pad=20)
    plt.ylabel('Kondisi Aktual (Ground Truth)', fontsize=12)
    plt.xlabel('Prediksi Sistem (Output)', fontsize=12)
    
    # Simpan gambar ke file
    filename = 'confusion_matrix_result.png'
    plt.savefig(filename, bbox_inches='tight', dpi=300)
    print(f"\n📸 Gambar Confusion Matrix berhasil disimpan sebagai '{filename}'")
    plt.close()

# =====================================================================
# 3. CORE EVALUATION LOGIC
# =====================================================================
def evaluate_full_metrics(sample_size=100, k=5):
    print("\n🚀 MEMULAI EVALUASI LENGKAP + VISUALISASI")
    print("=" * 70)

    # 1. Load Data
    df_products = get_products_with_categories()
    if df_products.empty:
        print("Data kosong atau gagal koneksi DB.")
        return

    # 2. Init Recommender
    print("⚙️  Membangun Model...")
    recommender = ContentRecommender()
    
    # 3. Sample Data
    actual_sample_size = min(sample_size, len(df_products))
    sample_products = df_products.sample(n=actual_sample_size, random_state=42)
    total_products_in_db = len(df_products)

    # Storage Metrics
    metrics = {
        'precision': [], 'recall': [], 'f1_score': [], 'accuracy': [], 'ndcg': [],
        'cm_tp': [], 'cm_fp': [], 'cm_fn': [], 'cm_tn': []
    }
    
    # Lookup Dictionaries (untuk mempercepat akses)
    product_categories = dict(zip(df_products['id'], df_products['category']))
    product_tags_map = dict(zip(df_products['id'], df_products['tags']))
    
    print("\n🔄 Sedang mengevaluasi...")
    
    for _, row in tqdm(sample_products.iterrows(), total=actual_sample_size):
        seed_id = row['id']
        seed_category = row['category']
        seed_tags_set = parse_tags(row['tags'])
        
        # --- A. DEFINISIKAN GROUND TRUTH (Relevansi di seluruh DB) ---
        # 1. Cek Kategori Sama
        cat_match = df_products['category'] == seed_category
        
        # 2. Cek Tags Overlap
        def check_tag_overlap(x):
            return not seed_tags_set.isdisjoint(parse_tags(x))
        
        tag_match = df_products['tags'].apply(check_tag_overlap)
        
        # Total item relevan di SELURUH DB (dikurangi 1 karena item itu sendiri tidak dihitung)
        is_relevant_series = (cat_match | tag_match)
        total_relevant_in_db = is_relevant_series.sum() - 1 
        
        if total_relevant_in_db == 0:
            continue # Skip jika item ini unik sendirian

        # --- B. DAPATKAN REKOMENDASI ---
        rec_ids = recommender.get_recs(seed_id, k=k)
        
        # --- C. HITUNG KOMPONEN CONFUSION MATRIX ---
        tp = 0
        relevant_binary_list = [] # List [1, 0, 1...] untuk perhitungan NDCG
        
        for r_id in rec_ids:
            # Cek detail item hasil rekomendasi
            r_cat = product_categories.get(r_id)
            r_tags_str = product_tags_map.get(r_id, "")
            r_tags_set = parse_tags(r_tags_str)
            
            # Logic Relevansi: Kategori Sama ATAU Tag Beririsan
            is_rel = (r_cat == seed_category) or (not seed_tags_set.isdisjoint(r_tags_set))
            
            if is_rel:
                tp += 1
                relevant_binary_list.append(1)
            else:
                relevant_binary_list.append(0)
        
        # Hitung sisa komponen CM
        fp = k - tp
        fn = total_relevant_in_db - tp
        # TN = (Total DB - 1 seed) - (Items in Recs) - (Items relevant but not in Recs)
        tn = (total_products_in_db - 1) - total_relevant_in_db - fp

        # --- D. HITUNG METRICS ---
        p = tp / k
        r_metric = tp / total_relevant_in_db if total_relevant_in_db > 0 else 0
        f1 = 2 * (p * r_metric) / (p + r_metric) if (p + r_metric) > 0 else 0
        acc = (tp + tn) / (total_products_in_db - 1)
        ndcg = calculate_ndcg(relevant_binary_list, k)

        # Simpan ke list
        metrics['precision'].append(p)
        metrics['recall'].append(r_metric)
        metrics['f1_score'].append(f1)
        metrics['accuracy'].append(acc)
        metrics['ndcg'].append(ndcg)
        
        metrics['cm_tp'].append(tp)
        metrics['cm_fp'].append(fp)
        metrics['cm_fn'].append(fn)
        metrics['cm_tn'].append(tn)

    # =================================================================
    # 4. LAPORAN HASIL
    # =================================================================
    # Hitung rata-rata untuk plotting
    avg_tp = np.mean(metrics['cm_tp'])
    avg_fp = np.mean(metrics['cm_fp'])
    avg_fn = np.mean(metrics['cm_fn'])
    avg_tn = np.mean(metrics['cm_tn'])

    print("\n" + "=" * 70)
    print(f"📊 HASIL EVALUASI LENGKAP (Rata-rata dari {actual_sample_size} sampel)")
    print("=" * 70)
    
    print(f"{'Metric':<20} | {'Score':<10} | {'Interpretasi'}")
    print("-" * 70)
    print(f"{'Precision@'+str(k):<20} | {np.mean(metrics['precision']):.4f}     | Akurasi dalam Top-{k} rekomendasi")
    print(f"{'Recall@'+str(k):<20}    | {np.mean(metrics['recall']):.4f}     | Cakupan barang relevan")
    print(f"{'F1-Score':<20}     | {np.mean(metrics['f1_score']):.4f}     | Harmonic mean Precision & Recall")
    print(f"{'NDCG@'+str(k):<20}      | {np.mean(metrics['ndcg']):.4f}     | Kualitas ranking")
    print(f"{'Accuracy':<20}     | {np.mean(metrics['accuracy']):.4f}     | Akurasi global (Bias tinggi)")
    
    print("-" * 70)
    print("📦 Confusion Matrix Text:")
    print(f"   [ TP: {avg_tp:.1f} | FP: {avg_fp:.1f} ]")
    print(f"   [ FN: {avg_fn:.1f} | TN: {avg_tn:.1f} ]")
    
    # Panggil fungsi plotting
    plot_confusion_matrix(avg_tp, avg_fp, avg_fn, avg_tn)

if __name__ == "__main__":
    # Jalankan evaluasi
    evaluate_full_metrics(sample_size=50, k=5)