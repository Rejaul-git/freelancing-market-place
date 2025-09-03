import React, { useState } from "react";
import "./Register.scss";
import axios from "axios";
import { useNavigate } from "react-router-dom";

function Register() {
  const [file, setFile] = useState(null);
  const [isSeller, setIsSeller] = useState(false);
  const [user, setUser] = useState({
    username: "",
    email: "",
    password: "",
    country: "",
    phone: "",
    des: "",
  });

  const handleChange = (e) => {
    setUser((prev) => ({
      ...prev,
      [e.target.name]: e.target.value,
    }));
  };

  const handleSeller = () => {
    setIsSeller((prev) => !prev);
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    const formData = new FormData();
    formData.append("username", user.username);
    formData.append("email", user.email);
    formData.append("password", user.password);
    formData.append("country", user.country);
    formData.append("phone", user.phone);
    formData.append("des", user.des);
    formData.append("role", isSeller ? "seller" : "buyer");

    if (file) {
      formData.append("img", file);
    }

    try {
      const res = await axios.post(
        "https://marketplace.brainstone.xyz/api/users/create_user.php",
        formData,
        {
          headers: { "Content-Type": "multipart/form-data" },
        }
      );
      alert(res.data.message);
      window.location.replace("/login");
    } catch (err) {
      console.error(err);
      alert("Registration failed");
    }
  };

  return (
    <div className="register">
      <form onSubmit={handleSubmit}>
        <div className="left">
          <h1>Create a new account</h1>

          <label>Username</label>
          <input
            name="username"
            type="text"
            placeholder="johndoe"
            onChange={handleChange}
          />

          <label>Email</label>
          <input
            name="email"
            type="email"
            placeholder="email"
            onChange={handleChange}
          />

          <label>Password</label>
          <input name="password" type="password" onChange={handleChange} />

          <label>Profile Picture</label>
          <input type="file" onChange={(e) => setFile(e.target.files[0])} />

          <label>Country</label>
          <input
            name="country"
            type="text"
            placeholder="Bangladesh"
            onChange={handleChange}
          />

          <button type="submit">Register</button>
        </div>

        <div className="right">
          <h1>I want to become a seller</h1>

          <div className="toggle">
            <label>Activate the seller account</label>
            <label className="switch">
              <input type="checkbox" onChange={handleSeller} />
              <span className="slider round"></span>
            </label>
          </div>

          <label>Phone Number</label>
          <input
            name="phone"
            type="text"
            placeholder="+88 0172345678"
            onChange={handleChange}
          />

          <label>Description</label>
          <textarea
            placeholder="A short description of yourself"
            name="des"
            cols="30"
            rows="10"
            onChange={handleChange}></textarea>
        </div>
      </form>
    </div>
  );
}

export default Register;
