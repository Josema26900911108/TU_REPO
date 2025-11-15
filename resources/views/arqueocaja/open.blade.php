<!DOCTYPE html>
<html>
<head>
    <title>Abrir Caja</title>
</head>
<body>
    <form action="{{ url('cash/open') }}" method="POST">
        @csrf
        <label for="initial_amount">Monto Inicial:</label>
        <input type="number" name="initial_amount" step="0.01" required>
        <button type="submit">Abrir Caja</button>
    </form>
</body>
</html>
