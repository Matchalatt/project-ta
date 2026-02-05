# Import necessary libraries
import pandas as pd
import numpy as np
import pymysql
import matplotlib.pyplot as plt
import seaborn as sns
from tqdm import tqdm
import warnings

# Ignore warnings for cleaner output
warnings.filterwarnings("ignore", category=UserWarning)
warnings.filterwarnings("ignore", category=FutureWarning)

# --- 1. Import Recommendation Functions (k-NN Only) ---
try:
    from recommend_knn import get_recommendations_for_user as get_knn_recs
    print("Successfully imported k-NN recommendation function.")
except ImportError as e:
    print(f"Failed to import modules: {e}")
    print("Ensure recommend_knn.py is in the same directory.")
    exit()

# --- 2. Database Configuration & Data Loading ---
DB_CONFIG = {
    'host': '127.0.0.1', 'user': 'root', 'password': '',
    'db': 'snackjuara', 'charset': 'utf8mb4'
}

def get_transaction_df():
    """Fetches all transaction data from the database."""
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
            print("Warning: No transaction data found.")
            return pd.DataFrame()
        print(f"Successfully loaded {len(df)} transaction rows.")
        return df
    except Exception as e:
        print(f"Database connection failed: {e}")
        return pd.DataFrame()
    finally:
        if 'connection' in locals() and connection.open:
            connection.close()

# --- 3. Data Preparation and Analysis ---
print("\n--- Starting Data Analysis ---")
df = get_transaction_df()

if not df.empty:
    # --- Train-Test Split (same as evaluation) ---
    train_df = df.sample(frac=0.7, random_state=42)
    test_df = df.drop(train_df.index)
    test_ground_truth = test_df.groupby('user_id')['product_id'].apply(list).to_dict()
    print(f"Data split into: {len(train_df)} train, {len(test_df)} test rows.")

    # --- Create User-Item Matrix (using training data) ---
    # We use the training data matrix as this is what the model learns from
    user_item_matrix_train = train_df.groupby(['user_id', 'product_id']).size().unstack(fill_value=0)
    # Convert to binary (purchased or not) as done in k-NN
    user_item_matrix_train[user_item_matrix_train > 0] = 1
    print(f"Train User-Item Matrix shape: {user_item_matrix_train.shape}")

    # --- a) Sparsity Analysis ---
    sparsity = 1.0 - (np.count_nonzero(user_item_matrix_train) / user_item_matrix_train.size)
    print(f"User-Item Matrix Sparsity: {sparsity:.4f} ({sparsity*100:.2f}%)")
    if sparsity > 0.95:
        print("-> The matrix is very sparse. k-NN will rely heavily on finding the few overlapping users.")
    else:
        print("-> The matrix sparsity is moderate to low.")


    # --- b) Product Popularity Analysis (using full dataset for context) ---
    product_popularity = df['product_id'].value_counts()
    plt.figure(figsize=(12, 6))
    sns.histplot(product_popularity.values, bins=30, kde=False)
    plt.title('Distribution of Product Popularity (Number of Times Purchased)')
    plt.xlabel('Number of Purchases')
    plt.ylabel('Number of Products')
    plt.yscale('log') # Use log scale if distribution is highly skewed
    plt.grid(axis='y', linestyle='--')
    plt.tight_layout()
    plt.savefig('product_popularity_distribution.png')
    print("\n-> Product popularity distribution saved to 'product_popularity_distribution.png'")
    print(f"   - Most popular product ID: {product_popularity.index[0]} (Purchased {product_popularity.iloc[0]} times)")
    print(f"   - Least popular product count: { (product_popularity == product_popularity.min()).sum()} products purchased only {product_popularity.min()} time(s)")
    print("   - Observe if there's a 'long tail'. k-NN tends to recommend popular items if neighbors are not very distinct.")


    # --- c) User Activity Analysis (using full dataset for context) ---
    user_activity = df.groupby('user_id')['product_id'].nunique() # Number of unique products per user
    plt.figure(figsize=(12, 6))
    sns.histplot(user_activity.values, bins=30, kde=False)
    plt.title('Distribution of User Activity (Number of Unique Products Purchased)')
    plt.xlabel('Number of Unique Products Purchased')
    plt.ylabel('Number of Users')
    # plt.yscale('log') # Optional: Use log scale if skewed
    plt.grid(axis='y', linestyle='--')
    plt.tight_layout()
    plt.savefig('user_activity_distribution.png')
    print("\n-> User activity distribution saved to 'user_activity_distribution.png'")
    print(f"   - Most active user ID: {user_activity.idxmax()} (Purchased {user_activity.max()} unique products)")
    print(f"   - Least active user count: {(user_activity == user_activity.min()).sum()} users purchased only {user_activity.min()} unique product(s)")
    print("   - High user activity usually improves k-NN accuracy as there are more connections to find neighbors.")

    # --- 4. Model Behavior Analysis (Qualitative on Sample Users - k-NN Only) ---
    print("\n--- Analyzing k-NN Recommendations for Sample Users ---")
    K = 5 # Number of recommendations
    sample_user_ids = list(test_ground_truth.keys())[:5] # Take first 5 users from test set

    for user_id in sample_user_ids:
        relevant_items = set(test_ground_truth.get(user_id, []))
        print(f"\nUser ID: {user_id}")
        print(f"  Actually Purchased (in test set): {list(relevant_items)}")

        # Get recommendations using the training data
        knn_recs = get_knn_recs(user_id, train_df, num_recommendations=K)
        
        knn_hits = set(knn_recs) & relevant_items

        print(f"  k-NN Recommendations: {knn_recs}")
        print(f"    -> Hits: {list(knn_hits)} ({len(knn_hits)}/{len(relevant_items)} relevant items found)")

        # Optional: Check popularity of recommended items
        knn_pop_scores = [product_popularity.get(pid, 0) for pid in knn_recs]
        if knn_pop_scores:
             print(f"    -> Avg Popularity of Recommended Items: {np.mean(knn_pop_scores):.1f}")


    print("\n--- Analysis Complete ---")

else:
    print("Could not load data. Aborting analysis.")