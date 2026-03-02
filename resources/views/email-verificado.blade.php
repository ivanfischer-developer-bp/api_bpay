<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verificado</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4; /* Fondo claro */
            color: #333; /* Color del texto */
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .mensaje {
            font-weight: 400; /* Peso de fuente normal */
            font-size: 1.2rem; /* Tamaño de fuente */
            color:rgb(15, 111, 37); /* Color del texto */
            text-align: center; /* Centrar el texto */
            line-height: 1.5; /* Espaciado entre líneas */
            padding: 20px;
            background-color:rgb(147, 243, 153); /* Fondo blanco */
            border: 1px solid rgba(22, 89, 9, 0.35); /* Borde gris claro */
            border-radius: 8px; /* Bordes redondeados */
            box-shadow: 0 4px 6px rgba(8, 64, 4, 0.51); /* Sombra ligera */
        }
    </style>
</head>
<body>
    <button class="mensaje" onclick="window.close()">
        Su email ha sido verificado con éxito. Ya puede cerrar esta ventana.
    </button>
</body>
</html>