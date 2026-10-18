#include <iostream>
#include <string>
using namespace std;

int main() {
int opcion;
string producto;
int entradas = 0, salidas = 0, existencia = 0;
int cantidad;

do {
    cout << "\n===== KARDEX DE INVENTARIO =====" << endl;
    cout << "1. Registrar entrada" << endl;
    cout << "2. Registrar salida" << endl;
    cout << "3. Ver inventario" << endl;
    cout << "4. Salir" << endl;
    cout << "Seleccione una opcion: ";
    cin >> opcion;

    switch (opcion) {
        case 1:
            cout << "Nombre del producto: ";
            cin >> producto;

            cout << "Cantidad que ingresa: ";
            cin >> cantidad;

            if (cantidad > 0) {
                entradas += cantidad;
                existencia += cantidad;
                cout << "Entrada registrada correctamente." << endl;
            } else {
                cout << "Cantidad no valida." << endl;
            }
            break;

        case 2:
            cout << "Cantidad que sale: ";
            cin >> cantidad;

            if (cantidad > 0 && cantidad <= existencia) {
                salidas += cantidad;
                existencia -= cantidad;
                cout << "Salida registrada correctamente." << endl;
            } else {
                cout << "Cantidad no valida o inventario insuficiente." << endl;
            }
            break;

        case 3:
            cout << "\n===== INVENTARIO ACTUAL =====" << endl;
            cout << "Producto: " << producto << endl;
            cout << "Total de entradas: " << entradas << endl;
            cout << "Total de salidas: " << salidas << endl;
            cout << "Existencia actual: " << existencia << endl;
            break;

        case 4:
            cout << "Saliendo del programa..." << endl;
            break;

        default:
            cout << "Opcion incorrecta." << endl;
    }

} while (opcion != 4);

return 0;

}