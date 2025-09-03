import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import { useApiSubmit, useApi } from "../../hooks/useApi";
import { useAuth } from "../../hooks/useApi";
import { gigsApi } from "../../services/api";
import "./MyGigs.scss";

function MyGigs() {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const [selectedGig, setSelectedGig] = useState(null);
  const [showEditModal, setShowEditModal] = useState(false);
  const [statusFilter, setStatusFilter] = useState("all");
  const [gigs, setGigs] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const imgUrl = "https://marketplace.brainstone.xyz/api/gigs/";

  // API hooks for deleting & updating gigs
  const { submit: deleteGig, loading: deleting } = useApiSubmit(gigsApi.delete);
  const { submit: updateGig, loading: updating } = useApiSubmit(gigsApi.update);

  // Fetch gigs without pagination
  const fetchGigs = async () => {
    try {
      setLoading(true);
      setError(null);
      const params = {
        user_id: user?.id,
        status: statusFilter !== "all" ? statusFilter : "all",
      };

      const res = await gigsApi.getAll(params);
      setGigs(res?.data || []);
    } catch (err) {
      setError(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (isAuthenticated && user?.id) {
      fetchGigs();
    }
  }, [statusFilter, user?.id, isAuthenticated]);

  // Handle gig deletion
  const handleDeleteGig = async (gigId) => {
    if (window.confirm("Are you sure you want to delete this gig?")) {
      try {
        await deleteGig({ id: gigId });
        fetchGigs();
        alert("Gig deleted successfully!");
      } catch (error) {
        alert("Failed to delete gig: " + error.message);
      }
    }
  };

  // Handle gig update
  const handleUpdateGig = async (gigData) => {
    try {
      await updateGig(gigData);
      setShowEditModal(false);
      setSelectedGig(null);
      fetchGigs();
      alert("Gig updated successfully!");
    } catch (error) {
      alert("Failed to update gig: " + error.message);
    }
  };

  // Handle gig action
  const handleGigAction = (gig, action) => {
    if (action === "edit") {
      setSelectedGig(gig);
      setShowEditModal(true);
    } else if (action === "delete") {
      handleDeleteGig(gig.id);
    }
  };

  if (authLoading) {
    return (
      <div className="myGigs">
        <div className="container">
          <div className="loading">Loading...</div>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="myGigs">
        <div className="container">
          <div className="auth-required">
            <p>Please log in to manage your gigs.</p>
            <Link to="/login" className="login-btn">
              Login
            </Link>
          </div>
        </div>
      </div>
    );
  }

  if (user?.role !== "seller") {
    return (
      <div className="myGigs">
        <div className="container">
          <div className="not-seller">
            <p>Only sellers can manage gigs.</p>
            <Link to="/gigs" className="browse-btn">
              Browse Gigs
            </Link>
          </div>
        </div>
      </div>
    );
  }

  if (loading) {
    return (
      <div className="myGigs">
        <div className="container">
          <div className="loading">Loading your gigs...</div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="myGigs">
        <div className="container">
          <div className="error">
            <p>Error loading gigs: {error.message}</p>
            <button onClick={fetchGigs}>Retry</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="myGigs">
      <div className="container">
        <div className="header">
          <div className="title">
            <h1>My Gigs</h1>
            <p>Manage your services and offerings</p>
          </div>

          <div className="actions">
            <div className="filters">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="status-filter">
                <option value="all">All Gigs</option>
                <option value="active">Active</option>
                <option value="paused">Paused</option>
                <option value="draft">Draft</option>
              </select>
            </div>

            <Link to="/add" className="add-gig-btn">
              + Add New Gig
            </Link>
          </div>
        </div>

        {gigs.length > 0 ? (
          <div className="gigs-table">
            <table>
              <thead>
                <tr>
                  <th>Gig</th>
                  <th>Category</th>
                  <th>Price</th>
                  <th>Status</th>
                  <th>Orders</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {gigs.map(
                  (gig) => (
                    console.log("Gig:", gig),
                    (
                      <tr key={gig.id}>
                        <td>
                          <div className="gig-info">
                            <img
                              className="gig-image"
                              src={imgUrl + gig.cover}
                              alt={gig.title}
                            />
                            <div className="gig-details">
                              <h4>{gig.title}</h4>
                              <p className="gig-short-desc">
                                {gig.short_desc && gig.short_desc.length > 50
                                  ? gig.short_desc.substring(0, 50) + "..."
                                  : gig.short_desc || "No description"}
                              </p>
                            </div>
                          </div>
                        </td>
                        <td>
                          <span className="category">
                            {gig.category || "Uncategorized"}
                          </span>
                        </td>
                        <td className="price">${gig.price}</td>
                        <td>
                          <span
                            className={`status-badge ${
                              gig.status || "active"
                            }`}>
                            {(gig.status || "active").charAt(0).toUpperCase() +
                              (gig.status || "active").slice(1)}
                          </span>
                        </td>
                        <td className="orders-count">
                          <span className="count">{gig.total_orders || 0}</span>
                          <small>orders</small>
                        </td>
                        <td>
                          <div className="actions">
                            <Link
                              to={`/gig/${gig.id}`}
                              className="view-btn"
                              title="View Gig">
                              👁️
                            </Link>
                            <button
                              className="edit-btn"
                              onClick={() => handleGigAction(gig, "edit")}
                              disabled={updating}
                              title="Edit Gig">
                              ✏️
                            </button>
                            <button
                              className="delete-btn"
                              onClick={() => handleGigAction(gig, "delete")}
                              disabled={deleting}
                              title="Delete Gig">
                              🗑️
                            </button>
                          </div>
                        </td>
                      </tr>
                    )
                  )
                )}
              </tbody>
            </table>
          </div>
        ) : (
          <div className="no-gigs">
            <div className="empty-state">
              <h3>No gigs found</h3>
              <p>
                You havent created any gigs yet. Start selling your services by
                creating your first gig!
              </p>
              <Link to="/add" className="create-btn">
                Create Your First Gig
              </Link>
            </div>
          </div>
        )}

        {showEditModal && selectedGig && (
          <EditGigModal
            gig={selectedGig}
            onSave={handleUpdateGig}
            onClose={() => {
              setShowEditModal(false);
              setSelectedGig(null);
            }}
            loading={updating}
          />
        )}
      </div>
    </div>
  );
}

// Edit Gig Modal
const EditGigModal = ({ gig, onSave, onClose, loading }) => {
  const [formData, setFormData] = useState({
    id: gig.id,
    title: gig.title || "",
    short_desc: gig.short_desc || "",
    price: gig.price || "",
    status: gig.status || "active",
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
          <h3>Edit Gig</h3>
          <button className="close-btn" onClick={onClose}>
            &times;
          </button>
        </div>

        <form onSubmit={handleSubmit} className="modal-body">
          <div className="form-group">
            <label>Title:</label>
            <input
              type="text"
              name="title"
              value={formData.title}
              onChange={handleChange}
              required
              maxLength="100"
            />
          </div>

          <div className="form-group">
            <label>Short Description:</label>
            <textarea
              name="short_desc"
              value={formData.short_desc}
              onChange={handleChange}
              placeholder="Brief description of your service..."
              rows="3"
              maxLength="200"
            />
          </div>

          <div className="form-group">
            <label>Price ($):</label>
            <input
              type="number"
              name="price"
              value={formData.price}
              onChange={handleChange}
              min="5"
              step="0.01"
              required
            />
          </div>

          <div className="form-group">
            <label>Status:</label>
            <select
              name="status"
              value={formData.status}
              onChange={handleChange}
              required>
              <option value="active">Active</option>
              <option value="paused">Paused</option>
              <option value="draft">Draft</option>
            </select>
          </div>

          <div className="modal-footer">
            <button type="button" onClick={onClose} disabled={loading}>
              Cancel
            </button>
            <button type="submit" disabled={loading}>
              {loading ? "Updating..." : "Update Gig"}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default MyGigs;
