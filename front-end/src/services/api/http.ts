import axios from 'axios';

export const BASE_URL =
    import.meta.env.VITE_BASE_URL || 'https://staffapp.uiidalwa.web.id/api';
export const ASSET_URL =
    import.meta.env.VITE_ASSET_URL || 'https://staffapp.uiidalwa.web.id/public_html';

const http = axios.create({
    baseURL: BASE_URL,
    timeout: 60000,
});

http.interceptors.request.use(
    (config) => {
        const token = localStorage.getItem('token');
        if (token) {
            config.headers.set('Authorization', `Bearer ${token}`);
            config.headers.set('Accept', 'application/json');
        }
        return config;
    },
);
export default http;
