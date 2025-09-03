import React, { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import "./Orders.scss";

const Orders = () => {
  const [orders, setOrders] = useState([]);
  const [search, setSearch] = useState("");
  const [status, setStatus] = useState("all");
  const [loading, setLoading] = useState(false);

  // sessionStorage থেকে user নিয়ে parse করা
  const userString = sessionStorage.getItem("user");
  const user = userString ? JSON.parse(userString) : null;

  const fetchOrders = async () => {
    if (!user) return; // user না থাকলে ফetch বন্ধ
    console.log("Fetching orders for user:", user, status, search);

    setLoading(true);
    try {
      const res = await axios.get(
        `https://marketplace.brainstone.xyz/api/buyer/orders.php`,
        {
          params: {
            search,
            status,
            user_id: user.id,
          },
        }
      );
      setOrders(res.data.data || []);
    } catch (err) {
      console.error("Error fetching orders:", err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();
  }, [search, status]);

  const getStatusBadge = (orderStatus) => {
    return (
      <span className={`status-badge ${orderStatus.toLowerCase()}`}>
        {orderStatus}
      </span>
    );
  };

  const formatDate = (dateString) => {
    if (!dateString) return "-";
    return new Date(dateString).toLocaleDateString();
  };

  const formatPrice = (price) => {
    return `$${parseFloat(price).toFixed(2)}`;
  };

  return (
    <div className="orders-container">
      <h2>Manage Orders</h2>

      {/* Filters */}
      <div className="filters">
        <input
          type="text"
          placeholder="Search by title or gig ID"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
          className="search-input"
        />
        <select
          value={status}
          onChange={(e) => setStatus(e.target.value)}
          className="status-select">
          <option value="all">All</option>
          <option value="pending">Pending</option>
          <option value="active">Active</option>
          <option value="inactive">Inactive</option>
          <option value="completed">Completed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>

      {/* Orders Table */}
      {loading ? (
        <div className="loading">Loading orders...</div>
      ) : (
        <div className="table-container">
          <table className="orders-table">
            <thead>
              <tr>
                <th>ID</th>
                <th>Gig Title</th>
                <th>Buyer ID</th>
                <th>Seller ID</th>
                <th>Price</th>
                <th>Status</th>
                <th>Deadline</th>
                <th>Delivery Date</th>
                <th>Created At</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {orders.length > 0 ? (
                orders.map((order) => (
                  <tr key={order.id}>
                    <td className="id">{order.id}</td>
                    <td className="title" title={order.gig_title}>
                      {order.gig_title}
                    </td>
                    <td>{order.buyer_id}</td>
                    <td>{order.seller_id}</td>
                    <td className="price">{formatPrice(order.price)}</td>
                    <td className="status">{getStatusBadge(order.status)}</td>
                    <td className="date">{formatDate(order.deadline)}</td>
                    <td className="date">{formatDate(order.delivery_date)}</td>
                    <td className="date">{formatDate(order.created_at)}</td>
                    <td className="actions">
                      <button
                        style={{
                          backgroundColor: "#ff4d4d",
                          color: "#fff",
                          padding: "8px 12px",
                          border: "none",
                          borderRadius: "4px",
                          cursor: "pointer",
                          marginRight: "8px",
                        }}>
                        Cancel Order
                      </button>
                    </td>
                  </tr>
                ))
              ) : (
                <tr className="no-orders">
                  <td colSpan="9">No orders found</td>
                </tr>
              )}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

export default Orders;
