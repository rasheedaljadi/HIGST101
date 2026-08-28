import sys
import paramiko
from pypdf import PdfReader

if hasattr(sys.stdout, 'reconfigure'):
    sys.stdout.reconfigure(encoding='utf-8', errors='replace')

reader = PdfReader("test_arabic_dompdf_local.pdf")
print("Extracted Text from DomPDF (UTF-8):")
for page in reader.pages:
    print(page.extract_text())
