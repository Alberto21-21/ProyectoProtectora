// DAR DE ALTA UN ANIMAL
export async function crearAnimal(datos, token) {

    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/animales",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            },
            body: datos
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        const error = new Error(
            resultado.message || "No se pudo registrar el animal."
        );

        // ERRORES DE CADA CAMPO QUE DEVUELVE LARAVEL
        error.errores = resultado.errors;

        throw error;
    }

    return resultado;
}