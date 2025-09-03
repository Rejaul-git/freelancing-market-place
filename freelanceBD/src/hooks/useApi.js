import { useState, useEffect, useCallback } from 'react';
import { handleApiError } from '../services/api';

// Generic API hook for CRUD operations
export const useApi = (apiFunction, dependencies = []) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const execute = useCallback(async (params) => {
    setLoading(true);
    setError(null);
    
    try {
      const result = await apiFunction(params);
      setData(result.data || result);
      return result;
    } catch (err) {
      const errorResult = handleApiError(err);
      setError(errorResult);
      throw err;
    } finally {
      setLoading(false);
    }
  }, dependencies);

  return { data, loading, error, execute, setData };
};

// Hook for paginated data
export const usePaginatedApi = (apiFunction, initialParams = {}) => {
  const [data, setData] = useState([]);
  const [pagination, setPagination] = useState({
    page: 1,
    limit: 10,
    total: 0,
    pages: 0
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [params, setParams] = useState(initialParams);

  const fetchData = useCallback(async (newParams = {}) => {
    setLoading(true);
    setError(null);
    
    const requestParams = { ...params, ...newParams };
    
    try {
      const result = await apiFunction(requestParams);
      setData(result.data || []);
      setPagination(result.pagination || pagination);
      setParams(requestParams);
      return result;
    } catch (err) {
      const errorResult = handleApiError(err);
      setError(errorResult);
      setData([]);
    } finally {
      setLoading(false);
    }
  }, [apiFunction, params]);

  const nextPage = () => {
    if (pagination.page < pagination.pages) {
      fetchData({ page: pagination.page + 1 });
    }
  };

  const prevPage = () => {
    if (pagination.page > 1) {
      fetchData({ page: pagination.page - 1 });
    }
  };

  const goToPage = (page) => {
    if (page >= 1 && page <= pagination.pages) {
      fetchData({ page });
    }
  };

  const updateParams = (newParams) => {
    fetchData({ ...newParams, page: 1 }); // Reset to first page when params change
  };

  useEffect(() => {
    fetchData();
  }, []);

  return {
    data,
    pagination,
    loading,
    error,
    fetchData,
    nextPage,
    prevPage,
    goToPage,
    updateParams,
    setData
  };
};

// Hook for form submissions
export const useApiSubmit = (apiFunction) => {
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(false);

  const submit = useCallback(async (data) => {
    setLoading(true);
    setError(null);
    setSuccess(false);
    
    try {
      const result = await apiFunction(data);
      setSuccess(true);
      return result;
    } catch (err) {
      const errorResult = handleApiError(err);
      setError(errorResult);
      throw err;
    } finally {
      setLoading(false);
    }
  }, [apiFunction]);

  const reset = () => {
    setError(null);
    setSuccess(false);
  };

  return { submit, loading, error, success, reset };
};

// Hook for real-time data updates
export const useRealTimeApi = (apiFunction, interval = 30000) => {
  const [data, setData] = useState(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [isRealTime, setIsRealTime] = useState(false);

  const fetchData = useCallback(async (params) => {
    if (!isRealTime) setLoading(true);
    setError(null);
    
    try {
      const result = await apiFunction(params);
      setData(result.data || result);
      return result;
    } catch (err) {
      const errorResult = handleApiError(err);
      setError(errorResult);
    } finally {
      if (!isRealTime) setLoading(false);
    }
  }, [apiFunction, isRealTime]);

  const startRealTime = useCallback((params) => {
    setIsRealTime(true);
    fetchData(params);
    
    const intervalId = setInterval(() => {
      fetchData(params);
    }, interval);
    
    return () => {
      clearInterval(intervalId);
      setIsRealTime(false);
    };
  }, [fetchData, interval]);

  return { data, loading, error, fetchData, startRealTime, isRealTime };
};

// Hook for authentication state
export const useAuth = () => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);
  const [isAuthenticated, setIsAuthenticated] = useState(false);

  const checkSession = useCallback(async () => {
    try {
      const { authApi } = await import('../services/api');
      const result = await authApi.checkSession();
      
      if (result.status === 'success' && result.user) {
        setUser(result.user);
        setIsAuthenticated(true);
      } else {
        setUser(null);
        setIsAuthenticated(false);
      }
    } catch (error) {
      setUser(null);
      setIsAuthenticated(false);
    } finally {
      setLoading(false);
    }
  }, []);

  const login = async (credentials) => {
    const { authApi } = await import('../services/api');
    const result = await authApi.login(credentials);
    
    if (result.status === 'success') {
      setUser(result.user);
      setIsAuthenticated(true);
    }
    
    return result;
  };

  const logout = async () => {
    try {
      const { authApi } = await import('../services/api');
      await authApi.logout();
    } catch (error) {
      console.error('Logout error:', error);
    } finally {
      setUser(null);
      setIsAuthenticated(false);
    }
  };

  useEffect(() => {
    checkSession();
  }, [checkSession]);

  return {
    user,
    loading,
    isAuthenticated,
    login,
    logout,
    checkSession
  };
};

// Hook for notifications
export const useNotifications = () => {
  const [notifications, setNotifications] = useState([]);
  const [unreadCount, setUnreadCount] = useState(0);
  const [loading, setLoading] = useState(false);

  const fetchNotifications = useCallback(async (params = {}) => {
    setLoading(true);
    try {
      const { notificationsApi } = await import('../services/api');
      const result = await notificationsApi.getAll(params);
      
      setNotifications(result.data || []);
      setUnreadCount(result.data?.filter(n => !n.is_read).length || 0);
    } catch (error) {
      console.error('Failed to fetch notifications:', error);
    } finally {
      setLoading(false);
    }
  }, []);

  const markAsRead = useCallback(async (notificationId) => {
    try {
      const { specializedApi } = await import('../services/api');
      await specializedApi.markNotificationAsRead(notificationId);
      
      setNotifications(prev => 
        prev.map(n => n.id === notificationId ? { ...n, is_read: 1 } : n)
      );
      setUnreadCount(prev => Math.max(0, prev - 1));
    } catch (error) {
      console.error('Failed to mark notification as read:', error);
    }
  }, []);

  useEffect(() => {
    fetchNotifications();
    
    // Poll for new notifications every 30 seconds
    const interval = setInterval(() => {
      fetchNotifications();
    }, 30000);
    
    return () => clearInterval(interval);
  }, [fetchNotifications]);

  return {
    notifications,
    unreadCount,
    loading,
    fetchNotifications,
    markAsRead
  };
};

// Hook for favorites
export const useFavorites = () => {
  const [favorites, setFavorites] = useState([]);
  const [loading, setLoading] = useState(false);

  const fetchFavorites = useCallback(async () => {
    setLoading(true);
    try {
      const { favoritesApi } = await import('../services/api');
      const result = await favoritesApi.getAll();
      setFavorites(result.data || []);
    } catch (error) {
      console.error('Failed to fetch favorites:', error);
    } finally {
      setLoading(false);
    }
  }, []);

  const addToFavorites = useCallback(async (gigId) => {
    try {
      const { specializedApi } = await import('../services/api');
      await specializedApi.addToFavorites(gigId);
      fetchFavorites(); // Refresh the list
    } catch (error) {
      console.error('Failed to add to favorites:', error);
      throw error;
    }
  }, [fetchFavorites]);

  const removeFromFavorites = useCallback(async (gigId) => {
    try {
      const { specializedApi } = await import('../services/api');
      await specializedApi.removeFromFavorites(gigId);
      fetchFavorites(); // Refresh the list
    } catch (error) {
      console.error('Failed to remove from favorites:', error);
      throw error;
    }
  }, [fetchFavorites]);

  const checkFavorite = useCallback(async (gigId) => {
    try {
      const { specializedApi } = await import('../services/api');
      const result = await specializedApi.checkFavorite(gigId);
      return result.is_favorited;
    } catch (error) {
      console.error('Failed to check favorite status:', error);
      return false;
    }
  }, []);

  useEffect(() => {
    fetchFavorites();
  }, [fetchFavorites]);

  return {
    favorites,
    loading,
    addToFavorites,
    removeFromFavorites,
    checkFavorite,
    fetchFavorites
  };
};

export default useApi;
