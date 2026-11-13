export async function obtenerDisponibilidades(filtros, token) {
    const parametros = new URLSearchParams({
        fecha_inicio: filtros.fecha_inicio,
        fecha_fin: filtros.fecha_fin
    });

    if (filtros.empleado_id) {
        parametros.set("empleado_id", filtros.empleado_id);
    }

    const respuesta = await fetch(
        `http://127.0.0.1:8000/api/disponibilidades?${parametros.toString()}`,
        {
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudieron consultar las disponibilidades."
        );
    }

    return resultado;
}

export async function crearDisponibilidad(datos, token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/disponibilidades",
        {
            method: "POST",
            headers: {
                "Accept": "application/json",
                "Content-Type": "application/json",
                "Authorization": "Bearer " + token
            },
            body: JSON.stringify(datos)
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudo registrar la disponibilidad."
        );
    }

    return resultado;
}

export async function obtenerEmpleadosParaDisponibilidad(token) {
    const respuesta = await fetch(
        "http://127.0.0.1:8000/api/disponibilidades/empleados",
        {
            headers: {
                "Accept": "application/json",
                "Authorization": "Bearer " + token
            }
        }
    );

    const resultado = await respuesta.json();

    if (!respuesta.ok) {
        throw new Error(
            resultado.message || "No se pudieron consultar los empleados."
        );
    }

    return resultado;
}