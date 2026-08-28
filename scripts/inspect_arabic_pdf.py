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
sftp.get(f"{APP_DIR}/public/test_arabic_dompdf.pdf", "test_arabic_dompdf_local.pdf")
sftp.close()
client.close()

reader = PdfReader("test_arabic_dompdf_local.pdf")
print("Extracted Text from DomPDF:")
for page in reader.pages:
    print(page.extract_text())
