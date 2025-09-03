import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import "./ManageGigs.scss";

const API_URL = "https://marketplace.brainstone.xyz/api/admin/gigs.php";

const ManageGigs = () => {
  const [gigs, setGigs] = useState([]);
  const [loading, setLoading] = useState(true);
  const [searchTerm, setSearchTerm] = useState("");
  const [filterStatus, setFilterStatus] = useState("all");
  const [currentPage, setCurrentPage] = useState(1);
  const [gigsPerPage] = useState(10);

  // Fetch gigs
  const fetchGigs = async () => {
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
        setGigs(data.data);
      }
    } catch (error) {
      console.error("Error fetching gigs:", error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchGigs();
    // eslint-disable-next-line
  }, [searchTerm, filterStatus]);

  // Toggle gig status
  const handleStatusToggle = async (gigId, currentStatus) => {
    const newStatus = currentStatus === "active" ? "inactive" : "active";
    try {
      const res = await fetch(API_URL, {
        method: "PUT",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ id: gigId, status: newStatus }),
      });
      const data = await res.json();
      if (data.status === "success") {
        setGigs(
          gigs.map((g) => (g.id === gigId ? { ...g, status: newStatus } : g))
        );
      } else {
        alert(data.message || "Failed to update status");
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Delete gig
  const handleDeleteGig = async (gigId) => {
    if (!window.confirm("Are you sure you want to delete this gig?")) return;
    try {
      const res = await fetch(API_URL, {
        method: "DELETE",
        headers: { "Content-Type": "application/json" },
        credentials: "include",
        body: JSON.stringify({ id: gigId }),
      });
      const data = await res.json();
      if (data.status === "success") {
        setGigs(gigs.filter((g) => g.id !== gigId));
      } else {
        alert(data.message || "Failed to delete gig");
      }
    } catch (err) {
      console.error(err);
    }
  };

  // Filter & pagination
  const filteredGigs = gigs.filter((gig) => {
    const matchesSearch =
      gig.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
      gig.seller_name.toLowerCase().includes(searchTerm.toLowerCase());
    const matchesStatus = filterStatus === "all" || gig.status === filterStatus;
    return matchesSearch && matchesStatus;
  });

  const indexOfLastGig = currentPage * gigsPerPage;
  const indexOfFirstGig = indexOfLastGig - gigsPerPage;
  const currentGigs = filteredGigs.slice(indexOfFirstGig, indexOfLastGig);
  const totalPages = Math.ceil(filteredGigs.length / gigsPerPage);

  if (loading) {
    return <div className="loading">Loading gigs...</div>;
  }

  return (
    <div className="manageGigs">
      <div className="container">
        <div className="header">
          <h1>Manage Gigs</h1>
          <div className="breadcrumb">
            <Link to="/admin">Admin Dashboard</Link> &gt; Manage Gigs
          </div>
        </div>

        {/* Filters and Search */}
        <div className="controls">
          <div className="searchBox">
            <input
              type="text"
              placeholder="Search gigs by title or seller..."
              value={searchTerm}
              onChange={(e) => {
                setSearchTerm(e.target.value);
                setCurrentPage(1);
              }}
            />
          </div>
          <div className="filters">
            <select
              value={filterStatus}
              onChange={(e) => {
                setFilterStatus(e.target.value);
                setCurrentPage(1);
              }}>
              <option value="all">All Status</option>
              <option value="active">Active</option>
              <option value="inactive">Inactive</option>
              <option value="pending">Pending</option>
            </select>
          </div>
        </div>

        {/* Gigs Table */}
        <div className="gigsTable">
          <table>
            <thead>
              <tr>
                <th>Image</th>
                <th>Title</th>
                <th>Seller</th>
                <th>Category</th>
                <th>Price</th>
                <th>Orders</th>
                <th>Status</th>
                <th>Created</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {currentGigs.map((gig) => (
                <tr key={gig.id}>
                  <td>
                    <img
                      src={gig.image_url.replace("api/uploads/", "api/gigs/")}
                      alt={gig.title}
                      className="gigImage"
                    />
                  </td>
                  <td>
                    <div className="gigTitle">
                      <h4>{gig.title}</h4>
                      <p>{gig.description?.substring(0, 50)}...</p>
                    </div>
                  </td>
                  <td>{gig.seller_name}</td>
                  <td>{gig.category || "Uncategorized"}</td>
                  <td>${gig.price}</td>
                  <td>{gig.orders_count || 0}</td>
                  <td>
                    <button
                      className={`statusBtn ${gig.status || "active"}`}
                      onClick={() =>
                        handleStatusToggle(gig.id, gig.status || "active")
                      }>
                      {gig.status || "active"}
                    </button>
                  </td>
                  <td>{new Date(gig.created_at).toLocaleDateString()}</td>
                  <td>
                    <div className="actions">
                      <Link to={`/gig/${gig.id}`} className="viewBtn">
                        View
                      </Link>
                      <button
                        className="deleteBtn"
                        onClick={() => handleDeleteGig(gig.id)}>
                        Delete
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>

        {/* Pagination */}
        {totalPages > 1 && (
          <div className="pagination">
            <button
              onClick={() => setCurrentPage((prev) => Math.max(prev - 1, 1))}
              disabled={currentPage === 1}>
              Previous
            </button>
            <span className="pageInfo">
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

        {/* Statistics */}
        <div className="gigStats">
          <div className="stat">
            <h3>{gigs.length}</h3>
            <p>Total Gigs</p>
          </div>
          <div className="stat">
            <h3>{gigs.filter((g) => g.status === "active").length}</h3>
            <p>Active Gigs</p>
          </div>
          <div className="stat">
            <h3>{gigs.filter((g) => g.status === "pending").length}</h3>
            <p>Pending Approval</p>
          </div>
          <div className="stat">
            <h3>{gigs.reduce((sum, g) => sum + (g.orders_count || 0), 0)}</h3>
            <p>Total Orders</p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ManageGigs;
