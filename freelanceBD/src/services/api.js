// Base API configuration and utilities
const API_BASE_URL = "https://marketplace.brainstone.xyz/api";

// HTTP client with default configuration
class ApiClient {
  constructor() {
    this.baseURL = API_BASE_URL;
  }

  async request(endpoint, options = {}) {
    const url = `${this.baseURL}${endpoint}`;

    const config = {
      credentials: "include", // Include cookies for session management
      headers: {
        "Content-Type": "application/json",
        ...options.headers,
      },
      ...options,
    };

    // Convert data to JSON if it's an object
    if (config.body && typeof config.body === "object") {
      config.body = JSON.stringify(config.body);
    }

    try {
      const response = await fetch(url, config);
      const data = await response.json();

      if (!response.ok) {
        throw new Error(
          data.message || `HTTP error! status: ${response.status}`
        );
      }

      return data;
    } catch (error) {
      console.error("API request failed:", error);
      throw error;
    }
  }

  // GET request
  async get(endpoint, params = {}) {
    const queryString = new URLSearchParams(params).toString();
    const url = queryString ? `${endpoint}?${queryString}` : endpoint;

    return this.request(url, {
      method: "GET",
    });
  }

  // POST request
  async post(endpoint, data = {}) {
    return this.request(endpoint, {
      method: "POST",
      body: data,
    });
  }

  // PUT request
  async put(endpoint, data = {}) {
    return this.request(endpoint, {
      method: "PUT",
      body: data,
    });
  }

  // DELETE request
  async delete(endpoint, data = {}) {
    return this.request(endpoint, {
      method: "DELETE",
      body: data,
    });
  }
}

// Create a singleton instance
const apiClient = new ApiClient();

// Generic CRUD operations
export const createCrudApi = (resourcePath) => ({
  // Get all items with pagination and filters
  getAll: (params = {}) => apiClient.get(`${resourcePath}/crud.php`, params),

  // Get single item by ID
  getById: (id) => apiClient.get(`${resourcePath}/crud.php`, { id }),

  // Create new item
  create: (data) => apiClient.post(`${resourcePath}/crud.php`, data),

  // Update existing item
  update: (data) => apiClient.put(`${resourcePath}/crud.php`, data),

  // Delete item
  delete: (data) => apiClient.delete(`${resourcePath}/crud.php`, data),
});

// Authentication API
export const authApi = {
  login: (credentials) => apiClient.post("/auth/login.php", credentials),
  register: (userData) => apiClient.post("/users/create_user.php", userData),
  logout: () => apiClient.post("/auth/logout.php"),
  checkSession: () => apiClient.get("/auth/check-session.php"),
};

// Dashboard APIs
export const dashboardApi = {
  getAdminStats: () => apiClient.get("/admin/dashboard-stats.php"),
  getSellerStats: () => apiClient.get("/seller/dashboard-stats.php"),
  getBuyerStats: () => apiClient.get("/buyer/dashboard-stats.php"),
  getRecentUsers: () => apiClient.get("/admin/recent-users.php"),
  getRecentOrders: (userType = "admin") =>
    apiClient.get(`/${userType}/recent-orders.php`),
};

// Export individual CRUD APIs for each resource
export const usersApi = createCrudApi("/users");
export const gigsApi = createCrudApi("/gigs");
export const ordersApi = createCrudApi("/orders");
export const messagesApi = createCrudApi("/messages");
export const reviewsApi = createCrudApi("/reviews");
export const categoriesApi = createCrudApi("/categories");
export const notificationsApi = createCrudApi("/notifications");
export const favoritesApi = createCrudApi("/favorites");
export const paymentsApi = createCrudApi("/payments");
export const earningsApi = createCrudApi("/earnings");
export const withdrawalsApi = createCrudApi("/withdrawals");
export const activityLogsApi = createCrudApi("/activity_logs");
export const systemSettingsApi = createCrudApi("/system_settings");

// Specialized API methods for specific use cases
export const specializedApi = {
  // Messages
  getConversations: (params = {}) =>
    apiClient.get("/messages/crud.php", { conversations: true, ...params }),
  getConversationMessages: (conversationId, params = {}) =>
    apiClient.get("/messages/crud.php", {
      conversation_id: conversationId,
      ...params,
    }),
  startConversation: (data) =>
    apiClient.post("/messages/crud.php", { start_conversation: true, ...data }),
  sendMessage: (data) => apiClient.post("/messages/crud.php", data),

  // Favorites
  checkFavorite: (gigId) =>
    apiClient.get("/favorites/crud.php", { check: true, gig_id: gigId }),
  addToFavorites: (gigId) =>
    apiClient.post("/favorites/crud.php", { gig_id: gigId }),
  removeFromFavorites: (gigId) =>
    apiClient.delete("/favorites/crud.php", { gig_id: gigId }),

  // Earnings
  getEarningsSummary: (sellerId) =>
    apiClient.get("/earnings/crud.php", { summary: true, seller_id: sellerId }),

  // System Settings
  getPublicSettings: () =>
    apiClient.get("/system_settings/crud.php", { public: true }),
  getSettingByKey: (key) => apiClient.get("/system_settings/crud.php", { key }),

  // Categories
  getCategoriesWithGigCount: (params = {}) =>
    apiClient.get("/categories/crud.php", { with_gig_count: true, ...params }),

  // Notifications
  markNotificationAsRead: (id) =>
    apiClient.put("/notifications/crud.php", { id, is_read: 1 }),
  markAllNotificationsAsRead: (userId) =>
    apiClient.put("/notifications/crud.php", {
      mark_all_read: true,
      user_id: userId,
    }),
};

// Error handling utility
export const handleApiError = (error) => {
  console.error("API Error:", error);

  if (error.message.includes("Unauthorized")) {
    // Redirect to login or show login modal
    window.location.href = "/login";
    return;
  }

  // Show error message to user (you can integrate with your notification system)
  return {
    status: "error",
    message: error.message || "An unexpected error occurred",
  };
};

// Loading state management utility
export const createApiHook = (apiFunction) => {
  return async (params) => {
    try {
      const result = await apiFunction(params);
      return { data: result.data, status: "success", error: null };
    } catch (error) {
      return { data: null, status: "error", error: handleApiError(error) };
    }
  };
};

export default apiClient;
