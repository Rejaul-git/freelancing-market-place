import React, { useState, useEffect } from "react";
import "./ManageOrders.scss";

const API_URL = "https://marketplace.brainstone.xyz/api/admin/orders.php";

const ManageOrders = () => {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterStatus, setFilterStatus] = useState("all");
  const [currentPage, setCurrentPage] = useState(1);
  const [ordersPerPage] = useState(10);

  // Fetch orders from API
  const fetchOrders = async () => {
    setLoading(true);
    try {
      const response = await fetch(
        `${API_URL}?search=${searchTerm}&status=${filterStatus}`,
        {
          credentials: "include",
        }
      );
      const data = await response.json();
      if (data.status === "success") {
        setOrders(data.data);
      } else {
        setOrders([]);
      }
    } catch (error) {
      console.error("Error fetching orders:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchOrders();
    // eslint-disable-next-line
  }, [searchTerm, filterStatus]);

  // Toggle order status (active/pending/canceled)
  const handleStatusToggle = async (orderId, currentStatus) => {
    // For demo: toggle between 'pending' and 'active'
    let newStatus;
    if (currentStatus === "active") newStatus = "pending";
    else if (currentStatus === "pending") newStatus = "active";
    else newStatus = "pending";

    try {
      const res = await fetch(API_URL, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ id: orderId, status: newStatus }),
      });
      const data = await res.json();
      if (data.status === "success") {
        setOrders(
          orders.map((o) =>
            o.id === orderId ? { ...o, status: newStatus } : o
          )
        );
      } else {
        alert(data.message || "Failed to update status");
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Delete order
  const handleDeleteOrder = async (orderId) => {
    if (!window.confirm("Are you sure you want to delete this order?")) return;
    try {
      const res = await fetch(API_URL, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ id: orderId }),
      });
      const data = await res.json();
      if (data.status === "success") {
        setOrders(orders.filter((o) => o.id !== orderId));
      } else {
        alert(data.message || "Failed to delete order");
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Filter & pagination
  const filteredOrders = orders.filter((order) => {
    const matchesSearch =
      order.gig_title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      order.buyer_id.toString().includes(searchTerm) ||
      order.seller_id.toString().includes(searchTerm);
    const matchesStatus =
      filterStatus === "all" || order.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  const indexOfLastOrder = currentPage * ordersPerPage;
  const indexOfFirstOrder = indexOfLastOrder - ordersPerPage;
  const currentOrders = filteredOrders.slice(
    indexOfFirstOrder,
    indexOfLastOrder
  );
  const totalPages = Math.ceil(filteredOrders.length / ordersPerPage);

  if (loading) {
    return <div className="loading">Loading orders...</div>;
  }

  return (
    <div className="manageOrders">
      <div className="container">
        <div className="header">
          <h1>Manage Orders</h1>
        </div>

        {/* Filters and Search */}
        <div className="controls">
          <input
            type="text"
            placeholder="Search by gig title, buyer ID, or seller ID..."
            value={searchTerm}
            onChange={(e) => {
              setSearchTerm(e.target.value);
              setCurrentPage(1);
            }}
          />
          <select
            value={filterStatus}
            onChange={(e) => {
              setFilterStatus(e.target.value);
              setCurrentPage(1);
            }}>
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="canceled">Canceled</option>
          </select>
        </div>

        {/* Orders Table */}
        <table>
          <thead>
            <tr>
              <th>Gig Image</th>
              <th>Gig Title</th>
              <th>Buyer ID</th>
              <th>Seller ID</th>
              <th>Price</th>
              <th>Status</th>
              <th>Deadline</th>
              <th>Delivery Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            {currentOrders.map((order) => (
              <tr key={order.id}>
                <td>
                  <img
                    src={`https://marketplace.brainstone.xyz/api/gigs/${order.gig_image}`}
                    alt={order.gig_title}
                    style={{
                      width: "80px",
                      height: "60px",
                      objectFit: "cover",
                    }}
                  />
                </td>
                <td>{order.gig_title}</td>
                <td>{order.buyer_id}</td>
                <td>{order.seller_id}</td>
                <td>${order.price}</td>
                <td>
                  <button
                    style={{ color: "white", backgroundColor: "green" }}
                    className={`statusBtn ${order.status}`}
                    onClick={() => handleStatusToggle(order.id, order.status)}>
                    {order.status}
                  </button>
                </td>
                <td>
                  {order.deadline
                    ? new Date(order.deadline).toLocaleDateString()
                    : "-"}
                </td>
                <td>
                  {order.delivery_date
                    ? new Date(order.delivery_date).toLocaleDateString()
                    : "-"}
                </td>
                <td>
                  <button
                    className="deleteBtn"
                    onClick={() => handleDeleteOrder(order.id)}>
                    Delete
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="pagination">
            <button
              onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
              disabled={currentPage === 1}>
              Previous
            </button>
            <span>
              Page {currentPage} of {totalPages}
            </span>
            <button
              onClick={() =>
                setCurrentPage((prev) => Math.min(prev + 1, totalPages))
              }
              disabled={currentPage === totalPages}>
              Next
            </button>
          </div>
        )}
      </div>
    </div>
  );
};

export default ManageOrders;
