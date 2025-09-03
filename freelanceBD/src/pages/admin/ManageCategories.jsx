import React, { useState, useEffect } from "react";
import { Link } from "react-router-dom";
import "./ManageCategories.scss";

const ManageCategories = () => {
  const [categories, setCategories] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [showAddForm, setShowAddForm] = useState(false);
  const [editingCategory, setEditingCategory] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");
  const [statusFilter, setStatusFilter] = useState("all");

  // Form state
  const [formData, setFormData] = useState({
    name: "",
    slug: "",
    description: "",
    icon: "",
    image: null,
    parent_id: null,
    sort_order: 0,
    status: "active",
  });

  // For file input
  const [imagePreview, setImagePreview] = useState("");

  // Fetch categories
  const fetchCategories = async () => {
    try {
      setLoading(true);
      const params = new URLSearchParams({
        search: searchTerm,
        status: statusFilter,
        with_gig_count: "true",
      });

      const response = await fetch(
        `https://marketplace.brainstone.xyz/api/categories/crud.php?${params}`
      );
      const result = await response.json();

      if (result.status === "success") {
        setCategories(result.data);
      } else {
        setError(result.message || "Failed to fetch categories");
      }
    } catch (err) {
      setError("Network error: " + err.message);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCategories();
  }, [searchTerm, statusFilter]);

  // Handle form input changes
  const handleInputChange = (e) => {
    const { name, value, files } = e.target;
    if (name === "image" && files && files[0]) {
      setFormData((prev) => ({
        ...prev,
        [name]: files[0],
      }));

      // Preview image
      const reader = new FileReader();
      reader.onload = (e) => {
        setImagePreview(e.target.result);
      };
      reader.readAsDataURL(files[0]);
    } else {
      setFormData((prev) => ({
        ...prev,
        [name]: value,
      }));
    }
  };

  // Add new category
  const handleAddCategory = async (e) => {
    e.preventDefault();
    try {
      // Use FormData for file uploads
      const formDataToSend = new FormData();
      Object.keys(formData).forEach((key) => {
        if (key !== "image") {
          formDataToSend.append(key, formData[key]);
        }
      });

      if (formData.image) {
        formDataToSend.append("image", formData.image);
      }

      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/categories/crud.php",
        {
          method: "POST",
          credentials: "include",
          body: formDataToSend,
        }
      );

      const result = await response.json();

      if (result.status === "success") {
        setShowAddForm(false);
        setFormData({
          name: "",
          slug: "",
          description: "",
          icon: "",
          image: null,
          parent_id: null,
          sort_order: 0,
          status: "active",
        });
        setImagePreview("");
        fetchCategories(); // Refresh the list
        alert("Category added successfully!");
      } else {
        alert("Error: " + result.message);
      }
    } catch (err) {
      alert("Network error: " + err.message);
    }
  };

  // Update category
  const handleUpdateCategory = async (e) => {
    e.preventDefault();
    try {
      // Use FormData for file uploads
      const formDataToSend = new FormData();
      Object.keys(formData).forEach((key) => {
        if (key !== "image") {
          formDataToSend.append(key, formData[key]);
        }
      });

      formDataToSend.append("id", editingCategory.id);

      if (formData.image) {
        formDataToSend.append("image", formData.image);
      }

      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/categories/crud.php",
        {
          method: "PUT",
          credentials: "include",
          body: formDataToSend,
        }
      );

      const result = await response.json();

      if (result.status === "success") {
        setEditingCategory(null);
        setFormData({
          name: "",
          slug: "",
          description: "",
          icon: "",
          image: null,
          parent_id: null,
          sort_order: 0,
          status: "active",
        });
        setImagePreview("");
        fetchCategories(); // Refresh the list
        alert("Category updated successfully!");
      } else {
        alert("Error: " + result.message);
      }
    } catch (err) {
      alert("Network error: " + err.message);
    }
  };

  // Delete category
  const handleDeleteCategory = async (category) => {
    if (
      !confirm(
        `Are you sure you want to delete the category "${category.name}"?`
      )
    ) {
      return;
    }

    try {
      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/categories/crud.php",
        {
          method: "DELETE",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({ id: category.id }),
        }
      );

      const result = await response.json();

      if (result.status === "success") {
        fetchCategories(); // Refresh the list
        alert("Category deleted successfully!");
      } else {
        alert("Error: " + result.message);
      }
    } catch (err) {
      alert("Network error: " + err.message);
    }
  };

  // Start editing
  const startEdit = (category) => {
    setEditingCategory(category);
    setFormData({
      name: category.name,
      slug: category.slug || "",
      description: category.description || "",
      icon: category.icon || "",
      image: null,
      parent_id: category.parent_id || null,
      sort_order: category.sort_order || 0,
      status: category.status,
    });
    setImagePreview(
      category.image
        ? `https://marketplace.brainstone.xyz/${category.image}`
        : ""
    );
    setShowAddForm(true);
  };

  // Cancel form
  const cancelForm = () => {
    setShowAddForm(false);
    setEditingCategory(null);
    setFormData({
      name: "",
      slug: "",
      description: "",
      icon: "",
      image: null,
      parent_id: null,
      sort_order: 0,
      status: "active",
    });
    setImagePreview("");
  };

  return (
    <div className="manageCategories">
      <div className="container">
        <div className="page-header">
          <div className="header-content">
            <h1>Manage Categories</h1>
            <p>Add, edit, and manage service categories</p>
          </div>
          <button onClick={() => setShowAddForm(true)} className="add-btn">
            + Add Category
          </button>
        </div>

        <div className="breadcrumb">
          <Link to="/">Home</Link> &gt;
          <Link to="/admin">Admin Dashboard</Link> &gt; Manage Categories
        </div>

        {/* Filters */}
        <div className="filters">
          <div className="search-box">
            <input
              type="text"
              placeholder="Search categories..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value)}
            className="status-filter">
            <option value="all">All Status</option>
            <option value="active">Active</option>
            <option value="inactive">Inactive</option>
          </select>
        </div>

        {/* Add/Edit Form Modal */}
        {showAddForm && (
          <div className="modal-overlay">
            <div className="modal">
              <div className="modal-header">
                <h2>
                  {editingCategory ? "Edit Category" : "Add New Category"}
                </h2>
                <button onClick={cancelForm} className="close-btn">
                  &times;
                </button>
              </div>
              <form
                onSubmit={
                  editingCategory ? handleUpdateCategory : handleAddCategory
                }>
                <div className="form-group">
                  <label>Category Name *</label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleInputChange}
                    required
                    placeholder="Enter category name"
                  />
                </div>
                <div className="form-group">
                  <label>Slug</label>
                  <input
                    type="text"
                    name="slug"
                    value={formData.slug}
                    onChange={handleInputChange}
                    placeholder="URL-friendly version (auto-generated if empty)"
                  />
                </div>
                <div className="form-group">
                  <label>Description</label>
                  <textarea
                    name="description"
                    value={formData.description}
                    onChange={handleInputChange}
                    placeholder="Enter category description"
                    rows="3"
                  />
                </div>
                <div className="form-row">
                  <div className="form-group">
                    <label>Icon</label>
                    <input
                      type="text"
                      name="icon"
                      value={formData.icon}
                      onChange={handleInputChange}
                      placeholder="Icon class or emoji"
                    />
                  </div>
                  <div className="form-group">
                    <label>Image</label>
                    <input
                      type="file"
                      name="image"
                      onChange={handleInputChange}
                      accept="image/*"
                    />
                    {imagePreview && (
                      <div className="image-preview">
                        <img src={imagePreview} alt="Preview" />
                      </div>
                    )}
                  </div>
                </div>
                <div className="form-group">
                  <label>Parent Category</label>
                  <select
                    name="parent_id"
                    value={formData.parent_id || ""}
                    onChange={handleInputChange}>
                    <option value="">None (Top Level)</option>
                    {categories
                      .filter((cat) => cat.id !== editingCategory?.id)
                      .map((category) => (
                        <option key={category.id} value={category.id}>
                          {category.name}
                        </option>
                      ))}
                  </select>
                </div>
                <div className="form-row">
                  <div className="form-group">
                    <label>Sort Order</label>
                    <input
                      type="number"
                      name="sort_order"
                      value={formData.sort_order}
                      onChange={handleInputChange}
                      min="0"
                    />
                  </div>
                  <div className="form-group">
                    <label>Status</label>
                    <select
                      name="status"
                      value={formData.status}
                      onChange={handleInputChange}>
                      <option value="active">Active</option>
                      <option value="inactive">Inactive</option>
                    </select>
                  </div>
                </div>

                <div className="form-actions">
                  <button
                    type="button"
                    onClick={cancelForm}
                    className="cancel-btn">
                    Cancel
                  </button>
                  <button type="submit" className="submit-btn">
                    {editingCategory ? "Update Category" : "Add Category"}
                  </button>
                </div>
              </form>
            </div>
          </div>
        )}

        {/* Categories List */}
        <div className="categories-section">
          {loading ? (
            <div className="loading">Loading categories...</div>
          ) : error ? (
            <div className="error">Error: {error}</div>
          ) : (
            <div className="categories-table">
              <div className="table-header">
                <div className="col-name">Name</div>
                <div className="col-description">Description</div>
                <div className="col-gigs">Gigs</div>
                <div className="col-status">Status</div>
                <div className="col-actions">Actions</div>
              </div>
              {categories.length > 0 ? (
                categories.map((category) => (
                  <div key={category.id} className="table-row">
                    <div className="col-name">
                      <div className="category-info">
                        {category.icon && (
                          <span className="category-icon">{category.icon}</span>
                        )}
                        <div>
                          <h4>{category.name}</h4>
                          <small>Order: {category.sort_order}</small>
                        </div>
                      </div>
                    </div>
                    <div className="col-description">
                      {category.description || "No description"}
                    </div>
                    <div className="col-gigs">
                      <span className="gig-count">
                        {category.gig_count || 0}
                      </span>
                    </div>
                    <div className="col-status">
                      <span className={`status ${category.status}`}>
                        {category.status}
                      </span>
                    </div>
                    <div className="col-actions">
                      <button
                        onClick={() => startEdit(category)}
                        className="edit-btn"
                        title="Edit">
                        ✏️
                      </button>
                      <button
                        onClick={() => handleDeleteCategory(category)}
                        className="delete-btn"
                        title="Delete"
                        disabled={category.gig_count > 0}>
                        🗑️
                      </button>
                    </div>
                  </div>
                ))
              ) : (
                <div className="no-data">
                  No categories found. Add your first category!
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default ManageCategories;
