// Base Configuration
const API_BASE_URL = 'https://demo.nuovasoft.in/Inclen2/admin/wp-json';

// Create a pre-configured Axios client
const apiClient = axios.create({
    baseURL: API_BASE_URL,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    }
});

const ApiService = {

    // 1. Governance & Team
    getGovernanceAll: async () => {
        try {
            const response = await apiClient.get('/governance/v1/all');
            return response.data;
        } catch (error) {
            console.error('API Error (getGovernanceAll):', error);
            throw error;
        }
    },

    // 2. Academic / Collaborator
    getAcademicAll: async () => {
        try {
            const response = await apiClient.get('/academic/v1/all');
            return response.data;
        } catch (error) {
            console.error('API Error (getAcademicAll):', error);
            throw error;
        }
    },

    // 3. Documents (Data & Tools)
    getDocumentAll: async () => {
        try {
            const response = await apiClient.get('/document/v1/all');
            return response.data;
        } catch (error) {
            console.error('API Error (getDocumentAll):', error);
            throw error;
        }
    },

    // 4. Research Studies
    getResearchAll: async () => {
        try {
            const response = await apiClient.get('/research/v1/all');
            return response.data;
        } catch (error) {
            console.error('API Error (getResearchAll):', error);
            throw error;
        }
    },

    // 5. Publications
    getPublicationsAll: async () => {
        try {
            const response = await apiClient.get('/publications/v1/all');
            return response.data;
        } catch (error) {
            console.error('API Error (getPublicationsAll):', error);
            throw error;
        }
    },
    // },

    // 3. Example API: POST data
    // submitContactForm: async (formData) => {
    //     const response = await apiClient.post('/contact/v1/submit', formData);
    //     return response.data;
    // }
};

// Expose globally so Alpine.js models and vanilla scripts can consume it
window.ApiService = ApiService;
