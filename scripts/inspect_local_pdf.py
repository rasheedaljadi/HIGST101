import paramiko
from pypdf import PdfReader

HOST = '76.13.79.242'
USER = 'highest-ye'
PASS = 'YoK2PBV1fo82yujX2tDq'
APP_DIR = '/home/highest-ye/htdocs/highest-ye.store'

client = paramiko.SSHClient()
client.set_missing_host_key_policy(paramiko.AutoAddPolicy())
client.connect(HOST, username=USER, password=PASS, timeout=20)

sftp = client.open_sftp()
sftp.get(f"{APP_DIR}/public/dompdf_cust.pdf", "dompdf_cust_local.pdf")
sftp.close()
client.close()

reader = PdfReader("dompdf_cust_local.pdf")
print("Local PDF page count:", len(reader.pages))
for i, page in enumerate(reader.pages):
    print(f"--- Page {i+1} Text ---")
    print(page.extract_text()[:500])
