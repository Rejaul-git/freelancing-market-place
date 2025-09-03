import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import "./AdminDashboard.scss";

const AdminDashboard = () => {
  // State for dashboard data
  const [stats, setStats] = useState(null);
  const [recentUsers, setRecentUsers] = useState([]);
  const [recentOrders, setRecentOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  // Fetch all dashboard data
  useEffect(() => {
    const fetchDashboardData = async () => {
      try {
        setLoading(true);

        // Fetch all data concurrently
        const [statsResponse, usersResponse, ordersResponse] =
          await Promise.all([
            axios.get(
              "https://marketplace.brainstone.xyz/api/admin/dashboard-stats.php",
              { withCredentials: true }
            ),
            axios.get(
              "https://marketplace.brainstone.xyz/api/admin/recent-users.php",
              { withCredentials: true }
            ),
            axios.get(
              "https://marketplace.brainstone.xyz/api/admin/recent-orders.php",
              { withCredentials: true }
            ),
          ]);

        setStats(statsResponse.data.data);
        setRecentUsers(usersResponse.data.data || []);
        setRecentOrders(ordersResponse.data.data || []);
      } catch (err) {
        console.error("Error fetching dashboard data:", err);
        setError("Failed to load dashboard data. Please try again later.");
      } finally {
        setLoading(false);
      }
    };

    fetchDashboardData();
  }, []);

  if (loading) {
    return (
      <div className="adminDashboard">
        <div className="container">
          <div className="loading">Loading dashboard data...</div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="adminDashboard">
        <div className="container">
          <div className="error">{error}</div>
        </div>
      </div>
    );
  }

  return (
    <div className="adminDashboard">
      <div className="container">
        <div className="dashboard-header">
          <div className="header-content">
            <h1>Admin Dashboard</h1>
            <p>Welcome back! Here's what's happening with your platform.</p>
          </div>
        </div>
        <div className="breadcrumb">
          <Link to="/">Home</Link> > Admin Dashboard
        </div>

        {/* Statistics Cards */}
        <div className="statsGrid">
          <div className="stat-card">
            <h3>{stats?.total_users || 0}</h3>
            <p>Total Users</p>
            <span className="stat-trend">📈</span>
          </div>
          <div className="stat-card">
            <h3>{stats?.total_gigs || 0}</h3>
            <p>Total Gigs</p>
            <span className="stat-trend">💼</span>
          </div>
          <div className="stat-card">
            <h3>{stats?.total_orders || 0}</h3>
            <p>Total Orders</p>
            <span className="stat-trend">📦</span>
          </div>
          <div className="stat-card">
            <h3>${stats?.total_revenue || 0}</h3>
            <p>Total Revenue</p>
            <span className="stat-trend">💰</span>
          </div>
          <div className="stat-card">
            <h3>{stats?.active_users || 0}</h3>
            <p>Active Users</p>
            <span className="stat-trend">👥</span>
          </div>
          <div className="stat-card">
            <h3>{stats?.pending_orders || 0}</h3>
            <p>Pending Orders</p>
            <span className="stat-trend">⏳</span>
          </div>
        </div>

        {/* Quick Actions */}
        <div className="quickActions">
          <h2>Quick Actions</h2>
          <div className="actionButtons">
            <Link to="/admin/users" className="actionBtn">
              <span>👥</span>
              Manage Users
            </Link>
            <Link to="/admin/gigs" className="actionBtn">
              <span>💼</span>
              Manage Gigs
            </Link>
            <Link to="/admin/orders" className="actionBtn">
              <span>📦</span>
              Manage Orders
            </Link>
            <Link to="/admin/reports" className="actionBtn">
              <span>📊</span>
              View Reports
            </Link>
            <Link to="/admin/settings" className="actionBtn">
              <span>⚙️</span>
              System Settings
            </Link>
            <Link to="/admin/categories" className="actionBtn">
              <span>📂</span>
              Manage Categories
            </Link>
          </div>
        </div>

        {/* Recent Activity */}
        <div className="recentActivity">
          <div className="section-header">
            <h2>Recent Users</h2>
            <Link to="/admin/users" className="view-all">
              View All
            </Link>
          </div>
          <div className="users-list">
            {recentUsers && recentUsers.length > 0 ? (
              recentUsers.map((user) => (
                <div key={user.id} className="user-item">
                  {/* <img
                    src={user.img || "/img/noavatar.jpg"}
                    alt={user.username}
                    onError={e => {
                      e.target.src = "/img/noavatar.jpg";
                    }}
                  /> */}
                  <div className="user-info">
                    <h4>{user.username}</h4>
                    <p>{user.email}</p>
                    <span className={`role ${user.role}`}>{user.role}</span>
                  </div>
                  <div className="user-date">
                    {new Date(user.created_at).toLocaleDateString()}
                  </div>
                </div>
              ))
            ) : (
              <div className="no-data">No recent users found</div>
            )}
          </div>
        </div>

        <div className="section-header">
          <h2>Recent Orders</h2>
          <Link to="/admin/orders" className="view-all">
            View All
          </Link>
        </div>
        <div className="orders-list">
          {recentOrders && recentOrders.length > 0 ? (
            recentOrders.map((order) => (
              <div key={order.id} className="order-item">
                <div className="order-info">
                  <h4>{order.gig_title}</h4>
                  <p>by {order.buyer_name}</p>
                  <span className={`status ${order.status}`}>
                    {order.status}
                  </span>
                </div>
                <div className="order-amount">${order.price}</div>
                <div className="order-date">
                  {new Date(order.created_at).toLocaleDateString()}
                </div>
              </div>
            ))
          ) : (
            <div className="no-data">No recent orders found</div>
          )}
        </div>
      </div>
    </div>
  );
};

export default AdminDashboard;
