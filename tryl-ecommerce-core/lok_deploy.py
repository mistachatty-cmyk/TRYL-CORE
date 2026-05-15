import os, sys, json, hashlib
import urllib.request, urllib.error

SITE_URL = "https://therighteousyieldlife.com"
API_KEY  = "S65zEWjSgQNqcsqhA49lymKFCiDIONtl"

API_ENDPOINT = f"{SITE_URL.rstrip('/')}/wp-json/lokservices/v1/deploy"

MANIFEST = {
    "plugins/tryl-ecommerce-core/tryl-ecommerce-core.php": "tryl-ecommerce-core/tryl-ecommerce-core.php",
    "plugins/tryl-ecommerce-core/templates/single-product.php": "tryl-ecommerce-core/templates/single-product.php",
    "plugins/tryl-ecommerce-core/templates/checkout/form-checkout.php": "tryl-ecommerce-core/templates/checkout/form-checkout.php",
    "plugins/tryl-ecommerce-core/templates/page-righteous-shop.php": "tryl-ecommerce-core/templates/page-righteous-shop.php",
    "plugins/tryl-editorial-skin/tryl-editorial-skin.php": "tryl-editorial-skin/tryl-editorial-skin.php",
    "plugins/lokservices-bridge/lokservices-bridge.php": "lokservices-bridge/lokservices-bridge.php",
    "mu-plugins/lokservices-bridge.php": "lokservices-bridge/lokservices-bridge.php",
    "mu-plugins/lokservices-bridge-mkdir.php": "lokservices-bridge-mkdir.php",
    "themes/divi-tryl-child/style.css": "divi-tryl-child/style.css",
    "themes/divi-tryl-child/functions.php": "divi-tryl-child/functions.php",
    "themes/divi-tryl-child/page-righteous-shop.php": "divi-tryl-child/page-righteous-shop.php",
    "themes/tryl-theme/functions.php": "tryl-website/tryl-theme/functions.php",
    "themes/tryl-theme/index.php": "tryl-website/tryl-theme/index.php",
    "themes/tryl-theme/page-righteous-shop.php": "tryl-website/tryl-theme/page-righteous-shop.php",
    "themes/tryl-theme/style.css": "tryl-website/frontend/style.css",
    "themes/tryl-theme/script.js": "tryl-website/frontend/script.js",
}

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
PROJECT_ROOT = os.path.dirname(BASE_DIR)  # scratch/

def resolve_local(relative_path):
    path = os.path.join(PROJECT_ROOT, relative_path.replace("/", os.sep))
    if os.path.isfile(path):
        return path
    alt = os.path.join(BASE_DIR, relative_path.replace("/", os.sep))
    if os.path.isfile(alt):
        return alt
    return path

def api_call(method, payload=None, path_suffix=""):
    url = API_ENDPOINT + path_suffix
    data = json.dumps(payload).encode("utf-8") if payload else None
    req = urllib.request.Request(url, method=method, data=data)
    req.add_header("Content-Type", "application/json")
    req.add_header("X-Lok-Key", API_KEY)
    req.add_header("User-Agent", "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36")
    try:
        resp = urllib.request.urlopen(req)
        return json.loads(resp.read().decode("utf-8-sig"))
    except urllib.error.HTTPError as e:
        body = e.read().decode("utf-8-sig")
        return {"error": e.code, "body": body[:300]}

def deploy_file(local_file, remote_path):
    local_file = resolve_local(local_file)
    if not os.path.isfile(local_file):
        return {"error": f"Local file not found: {local_file}"}
    with open(local_file, "r", encoding="utf-8") as f:
        content = f.read()
    checksum = hashlib.md5(content.encode("utf-8")).hexdigest()
    return api_call("POST", {"file_path": remote_path, "content": content, "checksum": checksum})

def cmd_deploy(args):
    if len(args) == 2:
        r = deploy_file(args[0], args[1])
        status = "OK" if "success" in r and r["success"] else f"FAIL ({r.get('error', r.get('body', '?'))})"
        print(f"  {args[1]} -> {status}")
        return

    print("Batch deploy all files...")
    ok = fail = 0
    for remote, local in MANIFEST.items():
        print(f"  {remote} ...", end=" ")
        r = deploy_file(local, remote)
        if "success" in r and r["success"]:
            print("OK")
            ok += 1
        else:
            print(f"FAIL: {r.get('error', r.get('body', '?'))}")
            fail += 1
    print(f"\nDone: {ok} succeeded, {fail} failed")

def cmd_status():
    print("Checking deployed file status via GET /deploy ...")
    r = api_call("GET", payload={"file_path": "_", "content": "_"})
    if "error" in r:
        print(f"Status check failed: {r}")
        return
    files = r.get("files", [])
    if not files:
        print("  No files recorded in manifest.")
        return
    ok = sum(1 for f in files if f["exists"])
    missing = [f["path"] for f in files if not f["exists"]]
    print(f"  {ok}/{len(files)} files present")
    if missing:
        print("  Missing:")
        for p in missing:
            print(f"    - {p}")

def cmd_single(args):
    if len(args) < 2:
        print("Usage: python lok_deploy.py deploy <local_file> <remote_path>")
        return
    cmd_deploy([args[0], args[1]])

if __name__ == "__main__":
    print("=== LokServices Bridge Deployer v2 ===\n")
    cmd = sys.argv[1] if len(sys.argv) > 1 else ""
    rest = sys.argv[2:]

    if cmd == "deploy":
        cmd_deploy(rest)
    elif cmd == "batch":
        cmd_deploy([])
    elif cmd == "status":
        cmd_status()
    else:
        print("Commands:")
        print("  python lok_deploy.py batch              Deploy all manifest files")
        print("  python lok_deploy.py deploy <local> <remote>  Deploy a single file")
        print("  python lok_deploy.py status             Check deployed file status")
