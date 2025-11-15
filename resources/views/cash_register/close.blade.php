<!DOCTYPE html>
<html>
<head>
    <title>Cerrar Caja</title>
</head>
<body>
    <form action="{{ url('cash-register/close/' . $cashRegister->id) }}" method="POST">
        @csrf
        <label for="closing_amount">Monto de Cierre:</label>
        <input type="number" name="closing_amount" step="0.01" required>
        <button type="submit">Cerrar Caja</button>
    </form>
</body>
</html>
