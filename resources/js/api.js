function getCsrf() {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/)
    return match ? decodeURIComponent(match[1]) : ''
}

async function request(url, method = 'GET', body = undefined) {
    const isFormData = body instanceof FormData

    const headers = {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-XSRF-TOKEN': getCsrf(),
        'X-Locale': localStorage.getItem('jomblo_locale') || 'nl',
    }

    if (body !== undefined && !isFormData) {
        headers['Content-Type'] = 'application/json'
    }

    const res = await fetch(url, {
        method,
        credentials: 'include',
        headers,
        body: body === undefined
            ? undefined
            : isFormData ? body : JSON.stringify(body),
    })

    const contentType = res.headers.get('Content-Type') ?? ''
    const data = contentType.includes('application/json') ? await res.json() : null

    if (!res.ok) {
        if (res.status === 401) {
            window.dispatchEvent(new CustomEvent('auth:expired'))
        }
        const err = new Error(data?.message ?? res.statusText)
        err.response = { status: res.status, data: data ?? {} }
        throw err
    }

    return { data }
}

export default {
    get:    (url)        => request(url, 'GET'),
    post:   (url, body)  => request(url, 'POST',   body),
    put:    (url, body)  => request(url, 'PUT',    body),
    delete: (url)        => request(url, 'DELETE'),
}
