import urllib.request
import re

url = "https://highest-ye.store/feelworld-lut7-7-inch-2200nit-touchscreen-4k-hdmi-camera-field-monitor-with-3d-lut-waveform-automatic-light-sensor-1920x1200"

req = urllib.request.Request(
    url, 
    headers={'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'}
)

try:
    with urllib.request.urlopen(req, timeout=15) as response:
        html = response.read().decode('utf-8')
        print(f"Fetched {len(html)} bytes")
        
        # Search for shipping related terms
        lines = html.split('\n')
        for i, line in enumerate(lines):
            if any(term in line for term in ['شحن', 'توصيل', 'shipping', 'delivery', 'ريال', '$']):
                print(f"Line {i+1}: {line.strip()[:200]}")
except Exception as e:
    print(f"Error fetching URL: {e}")
