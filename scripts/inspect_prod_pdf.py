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
sftp.get(f"{APP_DIR}/public/dompdf_prod_final.pdf", "dompdf_prod_local.pdf")
sftp.close()
client.close()

reader = PdfReader("dompdf_prod_local.pdf")
print("Local Products PDF page count:", len(reader.pages))
for i in range(min(3, len(reader.pages))):
    print(f"--- Page {i+1} Sample Text ---")
    print(reader.pages[i].extract_text()[:400])
