import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import "./ManageUsers.scss";

const API_BASE_URL = "https://marketplace.brainstone.xyz/api";

const ManageUsers = () => {
  const [users, setUsers] = useState([]);
  const [pagination, setPagination] = useState({
    page: 1,
    limit: 10,
    total: 0,
    pages: 0,
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  const [searchTerm, setSearchTerm] = useState("");
  const [filterRole, setFilterRole] = useState("all");
  const [filterStatus, setFilterStatus] = useState("all");

  const [editingUser, setEditingUser] = useState(null);
  const [showEditModal, setShowEditModal] = useState(false);
  const [updating, setUpdating] = useState(false);
  const [deleting, setDeleting] = useState(false);

  // Fetch users with filters and pagination
  const fetchUsers = async (params = {}) => {
    setLoading(true);
    setError(null);

    try {
      const response = await axios.get(`${API_BASE_URL}/users/crud.php`, {
        params: {
          page: pagination.page,
          limit: pagination.limit,
          search: searchTerm,
          role: filterRole !== "all" ? filterRole : "",
          status: filterStatus !== "all" ? filterStatus : "",
          ...params,
        },
        withCredentials: true,
      });

      if (response.data.status === "success") {
        setUsers(response.data.data || []);
        setPagination({
          ...pagination,
          ...response.data.pagination,
        });
      } else {
        setError(response.data.message || "Failed to fetch users");
      }
    } catch (err) {
      console.error("Failed to fetch users:", err);
      setError(
        err.response?.data?.message || err.message || "Failed to fetch users"
      );
    } finally {
      setLoading(false);
    }
  };

  // Update user
  const updateUser = async (userData) => {
    setUpdating(true);
    try {
      const response = await axios.put(
        `${API_BASE_URL}/users/crud.php`,
        userData,
        {
          withCredentials: true,
        }
      );

      if (response.data.status === "success") {
        return response.data;
      } else {
        throw new Error(response.data.message || "Failed to update user");
      }
    } finally {
      setUpdating(false);
    }
  };

  // Delete user
  const deleteUser = async (userId) => {
    setDeleting(true);
    try {
      const response = await axios.delete(`${API_BASE_URL}/users/crud.php`, {
        data: { id: userId },
        withCredentials: true,
      });

      if (response.data.status === "success") {
        return response.data;
      } else {
        throw new Error(response.data.message || "Failed to delete user");
      }
    } finally {
      setDeleting(false);
    }
  };

  // Initial fetch
  useEffect(() => {
    fetchUsers();
  }, [pagination.page, pagination.limit, searchTerm, filterRole, filterStatus]);

  // Handle search with debounce
  useEffect(() => {
    const timeoutId = setTimeout(() => {
      // Reset to first page when search or filters change
      setPagination((prev) => ({ ...prev, page: 1 }));
    }, 500);

    return () => clearTimeout(timeoutId);
  }, [searchTerm, filterRole, filterStatus]);

  // Handle user actions
  const handleEditUser = (user) => {
    setEditingUser(user);
    setShowEditModal(true);
  };

  const handleUpdateUser = async (userData) => {
    try {
      await updateUser(userData);
      setShowEditModal(false);
      setEditingUser(null);
      fetchUsers(); // Refresh the list
      alert("User updated successfully!");
    } catch (error) {
      alert("Failed to update user: " + error.message);
    }
  };

  const handleDeleteUser = async (userId) => {
    if (window.confirm("Are you sure you want to delete this user?")) {
      try {
        await deleteUser({ id: userId });
        fetchUsers(); // Refresh the list
        alert("User deleted successfully!");
      } catch (error) {
        alert("Failed to delete user: " + error.message);
      }
    }
  };

  const handleToggleStatus = async (user) => {
    const newStatus = user.status === "active" ? "inactive" : "active";
    try {
      await updateUser({ id: user.id, status: newStatus });
      fetchUsers(); // Refresh the list
      alert(
        `User ${
          newStatus === "active" ? "activated" : "deactivated"
        } successfully!`
      );
    } catch (error) {
      alert("Failed to update user status: " + error.message);
    }
  };

  const nextPage = () => {
    if (pagination.page < pagination.pages) {
      setPagination((prev) => ({ ...prev, page: prev.page + 1 }));
    }
  };

  const prevPage = () => {
    if (pagination.page > 1) {
      setPagination((prev) => ({ ...prev, page: prev.page - 1 }));
    }
  };

  const goToPage = (page) => {
    if (page >= 1 && page <= pagination.pages) {
      setPagination((prev) => ({ ...prev, page }));
    }
  };

  if (loading && users.length === 0) {
    return <div className="loading">Loading users...</div>;
  }

  return (
    <div className="manageUsers">
      <div className="container">
        <div className="header">
          <h1>Manage Users</h1>
          <div className="breadcrumb">
            <Link to="/admin">Admin Dashboard</Link> {">"} Manage Users
          </div>
        </div>

        {/* Filters and Search */}
        <div className="controls">
          <div className="searchBox">
            <input
              type="text"
              name="userSearch"
              placeholder="Search users by name or email..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <div className="filters">
            <select
              value={filterRole}
              onChange={(e) => setFilterRole(e.target.value)}>
              <option value="all">All Roles</option>
              <option value="admin">Admin</option>
              <option value="seller">Seller</option>
              <option value="buyer">Buyer</option>
            </select>
            <select
              value={filterStatus}
              onChange={(e) => setFilterStatus(e.target.value)}>
              <option value="all">All Statuses</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
            </select>
          </div>
          <Link to="/add" className="addBtn">
            Add New User
          </Link>
        </div>

        {/* Error message */}
        {error && <div className="error-message">{error}</div>}

        {/* Users Table */}
        <div className="usersTable">
          <table>
            <thead>
              <tr>
                <th>User</th>
                <th>Role</th>
                <th>Status</th>
                <th>Joined</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {users.map((user) => (
                <tr key={user.id}>
                  <td>
                    <div className="user-info">
                      <img
                        src={
                          user.img
                            ? `https://marketplace.brainstone.xyz/api/uploads/${user.img}`
                            : "/img/noavatar.jpg"
                        }
                        alt={user.username}
                        className="user-avatar"
                        onError={(e) => {
                          e.target.src = "/img/noavatar.jpg";
                        }}
                      />
                      <div>
                        <div className="username">{user.username}</div>
                        <div className="email">{user.email}</div>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span className={`role-badge ${user.role}`}>
                      {user.role}
                    </span>
                  </td>
                  <td>
                    <span className={`status-badge ${user.status}`}>
                      {user.status}
                    </span>
                  </td>
                  <td>{new Date(user.created_at).toLocaleDateString()}</td>
                  <td>
                    <div className="actions">
                      <button
                        className="btn-edit"
                        onClick={() => handleEditUser(user)}
                        disabled={updating}>
                        Edit
                      </button>
                      <button
                        className="btn-delete"
                        onClick={() => handleDeleteUser(user.id)}
                        disabled={deleting}>
                        Delete
                      </button>
                      <button
                        className="btn-toggle"
                        onClick={() => handleToggleStatus(user)}
                        disabled={updating}>
                        {user.status === "active" ? "Deactivate" : "Activate"}
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {users.length === 0 && !loading && (
            <div className="no-users">No users found</div>
          )}
        </div>

        {/* Pagination */}
        {pagination.pages > 1 && (
          <div className="pagination">
            <button
              onClick={prevPage}
              disabled={pagination.page === 1 || loading}>
              Previous
            </button>

            <span className="page-info">
              Page {pagination.page} of {pagination.pages} ({pagination.total}{" "}
              total users)
            </span>

            <div className="page-numbers">
              {Array.from({ length: Math.min(5, pagination.pages) }, (_, i) => {
                const pageNum = Math.max(1, pagination.page - 2) + i;
                if (pageNum <= pagination.pages) {
                  return (
                    <button
                      key={pageNum}
                      className={pagination.page === pageNum ? "active" : ""}
                      onClick={() => goToPage(pageNum)}
                      disabled={loading}>
                      {pageNum}
                    </button>
                  );
                }
                return null;
              })}
            </div>

            <button
              onClick={nextPage}
              disabled={pagination.page === pagination.pages || loading}>
              Next
            </button>
          </div>
        )}

        {/* Statistics */}
        <div className="userStats">
          <div className="stat">
            <h3>{users.length}</h3>
            <p>Total Users</p>
          </div>
          <div className="stat">
            <h3>{users.filter((u) => u.role === "admin").length}</h3>
            <p>Admins</p>
          </div>
          <div className="stat">
            <h3>{users.filter((u) => u.role === "seller").length}</h3>
            <p>Sellers</p>
          </div>
          <div className="stat">
            <h3>{users.filter((u) => u.role === "buyer").length}</h3>
            <p>Buyers</p>
          </div>
        </div>
      </div>

      {/* Edit User Modal */}
      {showEditModal && editingUser && (
        <EditUserModal
          user={editingUser}
          onSave={handleUpdateUser}
          onClose={() => {
            setShowEditModal(false);
            setEditingUser(null);
          }}
          loading={updating}
        />
      )}
    </div>
  );
};

// Edit User Modal Component
const EditUserModal = ({ user, onSave, onClose, loading }) => {
  const [formData, setFormData] = useState({
    id: user.id,
    username: user.username,
    email: user.email,
    role: user.role,
    status: user.status,
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    onSave(formData);
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value,
    });
  };

  return (
    <div className="modal-overlay">
      <div className="modal">
        <div className="modal-header">
          <h3>Edit User</h3>
          <button className="close-btn" onClick={onClose}>
            &times;
          </button>
        </div>

        <form onSubmit={handleSubmit} className="modal-body">
          <div className="form-group">
            <label>Username:</label>
            <input
              type="text"
              name="username"
              value={formData.username}
              onChange={handleChange}
              required
            />
          </div>

          <div className="form-group">
            <label>Email:</label>
            <input
              type="email"
              name="email"
              value={formData.email}
              onChange={handleChange}
              required
            />
          </div>

          <div className="form-group">
            <label>Role:</label>
            <select name="role" value={formData.role} onChange={handleChange}>
              <option value="buyer">Buyer</option>
              <option value="seller">Seller</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <div className="form-group">
            <label>Status:</label>
            <select
              name="status"
              value={formData.status}
              onChange={handleChange}>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="suspended">Suspended</option>
            </select>
          </div>

          <div className="modal-footer">
            <button type="button" onClick={onClose} disabled={loading}>
              Cancel
            </button>
            <button type="submit" disabled={loading}>
              {loading ? "Saving..." : "Save Changes"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ManageUsers;
