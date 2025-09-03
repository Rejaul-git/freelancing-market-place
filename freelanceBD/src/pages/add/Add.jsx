import React, { useState, useEffect } from "react";
import axios from "axios";
import "./Add.scss";

const Add = () => {
  const [form, setForm] = useState({
    title: "",
    cat: "",
    cover: null,
    images: [],
    desc: "",
    shortTitle: "",
    shortDesc: "",
    deliveryTime: "",
    revisionNumber: "",
    features: ["", "", "", ""],
    price: "",
  });

  const [categories, setCategories] = useState([]);
  const [loadingCategories, setLoadingCategories] = useState(true);

  // Fetch categories on component mount
  useEffect(() => {
    const fetchCategories = async () => {
      try {
        const response = await axios.get(
          "https://marketplace.brainstone.xyz/api/categories/crud.php?status=active"
        );
        if (response.data.status === "success") {
          setCategories(response.data.data);
        } else {
          console.error("Failed to fetch categories:", response.data.message);
        }
      } catch (error) {
        console.error("Error fetching categories:", error);
      } finally {
        setLoadingCategories(false);
      }
    };

    fetchCategories();
  }, []);

  const handleChange = (e) => {
    const { name, value, type, files } = e.target;

    if (type === "file" && name === "cover") {
      setForm({ ...form, cover: files[0] });
    } else if (type === "file" && name === "images") {
      setForm({ ...form, images: files });
    } else {
      setForm({ ...form, [name]: value });
    }
  };

  const handleFeatureChange = (index, value) => {
    const updated = [...form.features];
    updated[index] = value;
    setForm({ ...form, features: updated });
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    const currentUser = JSON.parse(sessionStorage.getItem("user"));

    const formData = new FormData();

    formData.append("user_id", currentUser.id);
    formData.append("title", form.title);
    formData.append("category", form.cat);
    formData.append("cover", form.cover);
    formData.append("description", form.desc);
    formData.append("shortTitle", form.shortTitle);
    formData.append("shortDescription", form.shortDesc);
    formData.append("deliveryTime", form.deliveryTime);
    formData.append("revisionNumber", form.revisionNumber);
    formData.append("price", form.price);

    // Multiple images
    for (let i = 0; i < form.images.length; i++) {
      formData.append("images[]", form.images[i]);
    }

    // Features as array
    for (let i = 0; i < form.features.length; i++) {
      if (form.features[i]) {
        formData.append("features[]", form.features[i]);
      }
    }

    try {
      const res = await axios.post(
        "https://marketplace.brainstone.xyz/api/gigs/create_gig.php",
        formData
      );
      alert("Gig created successfully!");
      console.log(res.data);
    } catch (error) {
      console.error(error);
      alert("Something went wrong!");
    }
  };

  return (
    <div className="add">
      <div className="container">
        <h1>Add New Gig</h1>
        <form className="sections" onSubmit={handleSubmit}>
          <div className="info">
            <label>Title</label>
            <input
              type="text"
              name="title"
              value={form.title}
              onChange={handleChange}
              placeholder="e.g. I will do something I'm really good at"
            />

            <label>Category</label>
            <select
              name="cat"
              value={form.cat}
              onChange={handleChange}
              disabled={loadingCategories}>
              <option value="">
                {loadingCategories
                  ? "Loading categories..."
                  : "Select a category"}
              </option>
              {categories.map((category) => (
                <option key={category.id} value={category.name}>
                  {category.icon && `${category.icon} `}
                  {category.name}
                </option>
              ))}
            </select>

            <label>Cover Image</label>
            <input type="file" name="cover" onChange={handleChange} />

            <label>Upload Images</label>
            <input type="file" name="images" multiple onChange={handleChange} />

            <label>Description</label>
            <textarea
              name="desc"
              value={form.desc}
              onChange={handleChange}
              placeholder="Brief description"
              rows="6"></textarea>

            <button type="submit">Create</button>
          </div>

          <div className="details">
            <label>Service Title</label>
            <input
              type="text"
              name="shortTitle"
              value={form.shortTitle}
              onChange={handleChange}
              placeholder="e.g. One-page web design"
            />

            <label>Short Description</label>
            <textarea
              name="shortDesc"
              value={form.shortDesc}
              onChange={handleChange}
              placeholder="Short description"
              rows="4"></textarea>

            <label>Delivery Time (e.g. 3 days)</label>
            <input
              type="number"
              name="deliveryTime"
              value={form.deliveryTime}
              onChange={handleChange}
            />

            <label>Revision Number</label>
            <input
              type="number"
              name="revisionNumber"
              value={form.revisionNumber}
              onChange={handleChange}
            />

            <label>Add Features</label>
            {form.features.map((f, i) => (
              <input
                key={i}
                type="text"
                value={f}
                onChange={(e) => handleFeatureChange(i, e.target.value)}
                placeholder={`Feature ${i + 1}`}
              />
            ))}

            <label>Price</label>
            <input
              type="number"
              name="price"
              value={form.price}
              onChange={handleChange}
            />
          </div>
        </form>
      </div>
    </div>
  );
};

export default Add;
