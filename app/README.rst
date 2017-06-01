Temat projektu: SERWIS OGŁOSZENIOWY – wynajem i sprzedaż nieruchomości


Funkcjonalności:

rejestracja użytkownika, logowanie, zmiana hasła, usuwanie konta
dodawanie, edytowanie, usuwanie ogłoszeń, załączanie do ogłoszenia zdjęć
przeglądanie ogłoszeń z uwzględnieniem kryteriów wyszukiwania: typ oferty (sprzedaż/wynajem), rodzaj nieruchomości, cena od/do, powierzchnia od/do, liczba pokoi
sortowanie wyniku wyszukiwania ogłoszeń wg kryteriów: cena rosnąco/malejąco, powierzchnia rosnąco/malejąco, data dodania
wysyłanie zapytań o ofertę (na email użytkownika, który zamieścił ogłoszenie)
dodawanie ogłoszeń do listy obserwowanych



Rzeczowniki:				Czasowniki:

użytkownik				rejestrowanie użytkownika (create), podgląd danych użytkownika (read), modyfikowanie danych użytkownika 						(update), kasowanie użytkownika (delete), logowanie ( + 						zmiana hasła), obserwowanie ogłoszenia

ogłoszenie				CRUD

zdjęcie					CRUD



ACL:

Rola:					Zasób:

użytkownik niezalogowany		ogłoszenie → read
					            użytkownik → create


użytkownik zalogowany		ogłoszenie → create, read, update i delete (tylko ogłoszenia stworzone przez zalogowanego użytkownika), dodaj ogłoszenie do obserwowanych
                            użytkownik → read, update, delete (tylko dane zalogowanego użytkownika)


administrator				ogłoszenie → create, read, update i delete (ogłoszenia wszystkich użytkowników)
                            użytkownik → delete (wszystkich użytkowników)