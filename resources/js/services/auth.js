import axios from 'axios';
import { ref } from 'vue';
import { v7 as uuidv7 } from 'uuid';
import { clearTacticalId } from '@/services/tactical_id';

/** 
 * @spec-link [[mechanic_mech_frontend_auth_bridge]]
 * @spec-link [[req_ui_session_timeout]]
 */
export const isSessionExpired = ref(false);
const auth = axios.create({
    baseURL: '/api/v1',
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

// Request Interceptor: Add JWT and Request ID
auth.interceptors.request.use((config) => {
    const token = localStorage.getItem('upsilon_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }

    // Add UUIDv7 Request ID if not present
    if (!config.headers['X-Request-ID']) {
        config.headers['X-Request-ID'] = uuidv7();
    }

    return config;
});

// Response Interceptor: Handle the Standard Envelope
auth.interceptors.response.use(
    (response) => {
        // All successful responses are wrapped in { data: { ... } } by Axios
        // The Upsilon envelope is { data: { success, data, message, request_id, meta } }
        const envelope = response.data;

        // Automatically update renewed tokens [[req_security_token_ttl]]
        if (envelope && envelope.meta && envelope.meta.token) {
            localStorage.setItem('upsilon_token', envelope.meta.token);
        }

        // Handle binary/file responses (no envelope)
        if (envelope instanceof Blob) {
            return envelope;
        }

        if (envelope && envelope.success) {
            return envelope.data; 
        }

        return Promise.reject(envelope);
    },
    (error) => {
        // Handle 401 Unauthorized (Session Expired)
        if (error.response && error.response.status === 401) {
            isSessionExpired.value = true;
            localStorage.removeItem('upsilon_token');
            localStorage.removeItem('upsilon_user');
            clearTacticalId();
        }

        // Error handling for 4xx/5xx (already wrapped by Laravel exception handler)
        if (error.response && error.response.data) {
            return Promise.reject(error.response.data);
        }
        return Promise.reject(error);
    }
);

export const login = async (credentials) => {
    const data = await auth.post('/auth/login', credentials);
    if (data.token) {
        localStorage.setItem('upsilon_token', data.token);
        localStorage.setItem('upsilon_user', JSON.stringify(data.user));
    }
    return data;
};

export const register = async (userData) => {
    const data = await auth.post('/auth/register', userData);
    if (data.token) {
        localStorage.setItem('upsilon_token', data.token);
        localStorage.setItem('upsilon_user', JSON.stringify(data.user));
    }
    return data;
};

export const logout = async () => {
    try {
        await auth.post('/auth/logout');
    } finally {
        localStorage.removeItem('upsilon_token');
        localStorage.removeItem('upsilon_user');
        clearTacticalId();
    }
};

export const getAuthUser = () => {
    const user = localStorage.getItem('upsilon_user');
    return user ? JSON.parse(user) : null;
};

export default auth;
