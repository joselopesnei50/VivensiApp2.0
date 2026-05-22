/**
 * Vivensi SDK v1.0
 * JavaScript client for the Vivensi Public API v1
 *
 * Usage:
 *   const api = new VivensiSDK({ token: 'your-api-token' });
 *   const txs = await api.transactions.list({ type: 'income' });
 *   await api.transactions.create({ description: 'Sale', amount: 100, date: '2026-05-22', type: 'income' });
 */

class VivensiSDK {
    constructor({ token, baseUrl = null }) {
        if (!token) throw new Error('VivensiSDK: token is required');
        this._token   = token;
        this._baseUrl = baseUrl || (window.location.origin + '/api/v1');

        this.me           = { show:   () => this._get('/me') };
        this.transactions = this._resource('/transactions');
        this.projects     = {
            list:   (params) => this._get('/projects', params),
            show:   (id)     => this._get(`/projects/${id}`),
            tasks:  (id, p)  => this._get(`/projects/${id}/tasks`, p),
        };
        this.tasks = this._resource('/tasks');
    }

    // ── Resource helper ───────────────────────────────────────────────────────

    _resource(path) {
        return {
            list:   (params) => this._get(path, params),
            show:   (id)     => this._get(`${path}/${id}`),
            create: (body)   => this._post(path, body),
            update: (id, b)  => this._patch(`${path}/${id}`, b),
            delete: (id)     => this._delete(`${path}/${id}`),
        };
    }

    // ── HTTP helpers ──────────────────────────────────────────────────────────

    async _request(method, path, body = null, params = null) {
        const url = new URL(this._baseUrl + path);
        if (params) {
            Object.entries(params).forEach(([k, v]) => {
                if (v !== undefined && v !== null) url.searchParams.set(k, v);
            });
        }

        const opts = {
            method,
            headers: {
                'Authorization': `Bearer ${this._token}`,
                'Accept':        'application/json',
                'Content-Type':  'application/json',
            },
        };
        if (body) opts.body = JSON.stringify(body);

        const res = await fetch(url.toString(), opts);

        if (res.status === 204) return null;

        const data = await res.json().catch(() => ({}));

        if (!res.ok) {
            const err = new Error(data.message || `API error ${res.status}`);
            err.status  = res.status;
            err.errors  = data.errors  || null;
            err.response = data;
            throw err;
        }

        return data;
    }

    _get(path, params)    { return this._request('GET',    path, null, params); }
    _post(path, body)     { return this._request('POST',   path, body); }
    _patch(path, body)    { return this._request('PATCH',  path, body); }
    _delete(path)         { return this._request('DELETE', path); }

    // ── Pagination helper ─────────────────────────────────────────────────────

    /**
     * Fetch all pages of a list endpoint.
     * @example const all = await api.fetchAll(api.transactions.list, { type: 'income' });
     */
    async fetchAll(listFn, params = {}, max = 1000) {
        let page    = 1;
        let results = [];
        let hasMore = true;

        while (hasMore && results.length < max) {
            const res = await listFn({ ...params, page, per_page: 100 });
            results   = results.concat(res.data || []);
            hasMore   = (res.meta?.current_page ?? 1) < (res.meta?.last_page ?? 1);
            page++;
        }

        return results;
    }
}

if (typeof module !== 'undefined' && module.exports) {
    module.exports = VivensiSDK;
}
