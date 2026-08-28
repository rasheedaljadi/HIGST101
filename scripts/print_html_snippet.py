import urllib.request

url = "https://highest-ye.store/feelworld-lut7-7-inch-2200nit-touchscreen-4k-hdmi-camera-field-monitor-with-3d-lut-waveform-automatic-light-sensor-1920x1200"
req = urllib.request.Request(url, headers={'User-Agent': 'Mozilla/5.0'})
with urllib.request.urlopen(req) as resp:
    html = resp.read().decode('utf-8')

lines = html.splitlines()
for idx in range(8020, min(len(lines), 8160)):
    print(f"{idx+1}: {lines[idx]}")
