import urllib.request
import re

req = urllib.request.Request('https://highest-ye.store/search?query=a', headers={'User-Agent': 'Mozilla/5.0'})
try:
    with urllib.request.urlopen(req) as resp:
        html = resp.read().decode('utf-8')
        pos = html.find('<!-- Product Image -->')
        if pos != -1:
            print("=== IMG TEMPLATE ===")
            print(html[pos:pos+1000])
except Exception as e:
    print('Error:', e)
