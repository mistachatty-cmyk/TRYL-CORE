import os
import sys
import json
import urllib.request
import urllib.error

# ==========================================
# LOKSERVICES DEPLOYMENT CONFIGURATION
# ==========================================
SITE_URL = "https://therighteousyieldlife.com" # Change if using a staging site
API_KEY = "YOUR_SECRET_API_KEY_HERE"           # Paste your key from the WP Dashboard
# ==========================================

API_ENDPOINT = f"{SITE_URL.rstrip('/')}/wp-json/lokservices/v1/deploy"

def deploy(local_file, remote_path):
    if not os.path.isfile(local_file):
        print(f"❌ Error: Local file '{local_file}' does not exist.")
        sys.exit(1)

    print(f"🚀 Preparing to deploy '{local_file}'...")
    
    try:
        with open(local_file, 'r', encoding='utf-8') as f:
            content = f.read()
    except Exception as e:
        print(f"❌ Error reading file: {e}")
        sys.exit(1)

    payload = {
        "file_path": remote_path,
        "content": content
    }

    req = urllib.request.Request(API_ENDPOINT, method="POST")
    req.add_header("Content-Type", "application/json")
    req.add_header("X-Lok-Key", API_KEY)
    
    data = json.dumps(payload).encode("utf-8")

    try:
        print(f"📡 Sending to endpoint (Target: {remote_path})")
        response = urllib.request.urlopen(req, data=data)
        result = json.loads(response.read().decode('utf-8'))
        print(f"✅ Success! {result.get('message', '')}")
        
    except urllib.error.HTTPError as e:
        print(f"❌ HTTP Error {e.code}: Deployment failed.")
        try:
            err_data = json.loads(e.read().decode('utf-8'))
            print(f"   Reason: {err_data.get('message', 'Unknown')}")
        except:
            print(f"   Reason: {e.reason}")
    except urllib.error.URLError as e:
        print(f"❌ Connection Error: {e.reason}")
    except Exception as e:
        print(f"❌ Unexpected Error: {e}")

if __name__ == "__main__":
    print("--- LokServices Bridge Deployer ---\n")
    
    # Command Line Mode vs Interactive Mode
    if len(sys.argv) == 3:
        local_f = sys.argv[1]
        remote_p = sys.argv[2]
    else:
        print("Interactive Mode:")
        print("Tip: You can skip this prompt next time by running: python lok_deploy.py <local_file> <remote_path>\n")
        local_f = input("Enter the path to your local file (e.g., tryl-ecommerce-core.php): ").strip()
        remote_p = input("Enter the destination path in wp-content (e.g., plugins/tryl-ecommerce-core/tryl-ecommerce-core.php): ").strip()
        print("")

    if local_f and remote_p:
        deploy(local_f, remote_p)
    else:
        print("❌ Invalid input. Both local and remote paths are required.")
        print("Usage: python lok_deploy.py <local_file_path> <remote_wp_content_path>")