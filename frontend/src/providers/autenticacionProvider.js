
// ENVIAR LOS DATOS DEL REGISTRO
export async function registrarUsuario(datos) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/register",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json"
            },
            body: JSON.stringify(datos)
        }
    );

    if (!respuesta.ok) {
        throw new Error(`Error HTTP: ${respuesta.status}`);
    }

    return await respuesta.json();
}
