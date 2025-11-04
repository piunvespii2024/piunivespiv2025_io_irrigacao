import serial
import requests
import time

# Configura a porta serial (ajuste se necessário)
PORTA = "COM5"
BAUDRATE = 9600
URL = "http://localhost/irrigacao/api/salvar_dados.php"

def main():
    try:
        ser = serial.Serial(PORTA, BAUDRATE, timeout=2)
        print(f"Conectado à porta {PORTA} a {BAUDRATE} baud.")
    except Exception as e:
        print("Erro ao abrir porta serial:", e)
        return

    while True:
        try:
            # Lê uma linha da serial
            linha = ser.readline().decode(errors='ignore').strip()
            if not linha:
                continue

            print("Recebido da serial:", linha)

            # Converte string "umidade=45.6&status=Solo seco" em dict
            payload = {}
            for par in linha.split("&"):
                if "=" in par:
                    chave, valor = par.split("=", 1)
                    payload[chave] = valor

            if payload:
                print("Enviando para servidor:", payload)
                try:
                    r = requests.post(URL, data=payload, timeout=5)
                    print("Resposta do servidor:", r.text)
                except Exception as e:
                    print("Erro ao enviar para servidor:", e)

            time.sleep(1)  # evita sobrecarga

        except KeyboardInterrupt:
            print("\nExecução interrompida pelo usuário.")
            break
        except Exception as e:
            print("Erro inesperado:", e)
            time.sleep(2)

if __name__ == "__main__":
    main()
