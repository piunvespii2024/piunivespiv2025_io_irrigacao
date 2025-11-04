import serial
import requests

# Porta serial do Arduino
ser = serial.Serial('COM5', 9600, timeout=2)

# URLs de destino
URL_LOCAL = "http://localhost/irrigacao/api/salvar_dados.php"
URL_REMOTO = "https://softdesignersolutionsltda.com.br/api/salvar_dados.php"

while True:
    linha = ser.readline().decode(errors='ignore').strip()
    if linha:
        print("Recebido da serial:", linha)

        # Converte para dicionário
        payload = {}
        for par in linha.split("&"):
            if "=" in par:
                chave, valor = par.split("=", 1)
                payload[chave] = valor

        print("Payload enviado:", payload)

        # Envia para o servidor local
        try:
            r_local = requests.post(URL_LOCAL, data=payload, timeout=10)
            print("[LOCAL] Resposta:", r_local.text)
        except Exception as e:
            print("[LOCAL] Erro ao enviar:", e)

        # Envia também para o servidor remoto
        try:
            r_remoto = requests.post(URL_REMOTO, data=payload, timeout=10)
            print("[REMOTO] Resposta:", r_remoto.text)
        except Exception as e:
            print("[REMOTO] Erro ao enviar:", e)
