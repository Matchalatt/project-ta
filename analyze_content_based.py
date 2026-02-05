# Import necessary libraries
import pandas as pd
import numpy as np
import pymysql
import matplotlib.pyplot as plt
import seaborn as sns
from collections import Counter
import warnings

# Ignore warnings for cleaner output
warnings.filterwarnings("ignore", category=UserWarning)
warnings.filterwarnings("ignore", category=FutureWarning)

# --- 1. Import Recommendation Functions (TF-IDF Only) ---
try:
    # Kita mengimport fungsi core dari file recommend_tfidf yang sudah Anda upload
    from recommend_tfidf import get_recommendations
    print("Successfully imported TF-IDF recommendation function.")
except ImportError as e:
    print(f"Failed to import modules: {e}")
    print("Ensure recommend_tfidf.py is in the same directory.")
    exit()

# --- 2. Database Configuration & Data Loading ---
DB_CONFIG = {
    'host': '127.0.0.1', 'user': 'root', 'password': '',
    'db': 'snackjuara', 'charset': 'utf8mb4'
}

def get_product_df():
    """Fetches all product data (names and tags) from the database."""
    try:
        connection = pymysql.connect(**DB_CONFIG)
        query = "SELECT id, name, tags FROM products"
        df = pd.read_sql(query, connection)
        if len(df) == 0:
            print("Warning: No product data found.")
            return pd.DataFrame()
        print(f"Successfully loaded {len(df)} products.")
        return df
    except Exception as e:
        print(f"Database connection failed: {e}")
        return pd.DataFrame()
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

# --- 3. Data Preparation and Analysis ---
print("\n--- Starting Content-Based Data Analysis ---")
df = get_product_df()

if not df.empty:
    # Pre-processing tags (fill NaN and lowercase)
    df['tags'] = df['tags'].fillna('').str.lower()
    
    # --- a) Tag Frequency Analysis (Word Count) ---
    # Menggabungkan semua tags menjadi satu list besar untuk dihitung
    all_tags = [tag.strip() for sublist in df['tags'].str.split(',') for tag in sublist if tag.strip()]
    tag_counts = Counter(all_tags)
    
    # Convert to DataFrame for plotting
    tag_df = pd.DataFrame(tag_counts.most_common(20), columns=['Tag', 'Frequency'])
    
    plt.figure(figsize=(12, 6))
    sns.barplot(data=tag_df, x='Frequency', y='Tag', palette='viridis')
    plt.title('Top 20 Most Frequent Tags (Keywords)')
    plt.xlabel('Frequency')
    plt.ylabel('Tag Name')
    plt.grid(axis='x', linestyle='--')
    plt.tight_layout()
    plt.savefig('tag_frequency_distribution.png')
    print("\n-> Tag frequency distribution saved to 'tag_frequency_distribution.png'")
    print(f"   - Total unique tags found: {len(tag_counts)}")
    print(f"   - Most common tag: '{tag_df.iloc[0]['Tag']}' (Used {tag_df.iloc[0]['Frequency']} times)")
    
    # --- b) Tags per Product Analysis ---
    # Menghitung berapa banyak tag yang dimiliki setiap produk
    df['tag_count'] = df['tags'].apply(lambda x: len([t for t in x.split(',') if t.strip()]))
    
    plt.figure(figsize=(12, 6))
    sns.histplot(df['tag_count'], bins=range(0, df['tag_count'].max() + 2), discrete=True, kde=False)
    plt.title('Distribution of Tags per Product')
    plt.xlabel('Number of Tags')
    plt.ylabel('Number of Products')
    plt.grid(axis='y', linestyle='--')
    plt.tight_layout()
    plt.savefig('tags_per_product_distribution.png')
    print("\n-> Tags per product distribution saved to 'tags_per_product_distribution.png'")
    print(f"   - Average tags per product: {df['tag_count'].mean():.2f}")
    print(f"   - Products with NO tags: {len(df[df['tag_count'] == 0])} (These products will have poor recommendations)")

    # --- 4. Model Behavior Analysis (Qualitative on Sample Products) ---
    print("\n--- Analyzing TF-IDF Recommendations for Sample Products ---")
    
    # Ambil 5 produk acak yang memiliki tags (agar hasil tes valid)
    sample_products = df[df['tag_count'] > 0].sample(n=min(5, len(df)), random_state=42)
    
    for index, row in sample_products.iterrows():
        p_id = row['id']
        p_name = row['name']
        p_tags = row['tags']
        
        print(f"\nTarget Product: {p_name} (ID: {p_id})")
        print(f"  Tags: [{p_tags}]")

        # Get recommendations using the imported function
        # Kita set verbose=False agar tidak spamming log heatmap untuk setiap loop
        rec_ids = get_recommendations(p_id, num_recommendations=5, verbose=False)
        
        if rec_ids:
            print("  Recommendations (Content-Based):")
            # Lookup nama produk dan tags dari hasil rekomendasi
            rec_details = df[df['id'].isin(rec_ids)]
            
            # Kita loop manual agar urutan sesuai output function (bukan urutan index dataframe)
            found_recs = 0
            for r_id in rec_ids:
                item = df[df['id'] == r_id]
                if not item.empty:
                    r_name = item.iloc[0]['name']
                    r_tags = item.iloc[0]['tags']
                    print(f"    -> {r_name} \n       (Tags: {r_tags})")
                    found_recs += 1
            
            if found_recs == 0:
                 print("    -> No matching products found in local dataframe (Logic Check).")
        else:
            print("    -> No recommendations returned (Possible error or no similarity).")

    print("\n--- Analysis Complete ---")

else:
    print("Could not load data. Aborting analysis.")