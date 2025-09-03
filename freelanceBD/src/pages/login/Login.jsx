import React, { useState } from "react";
import "./Login.scss";
import { useNavigate } from "react-router-dom";

function Login() {
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [message, setMessage] = useState("");

  const navigate = useNavigate();

  const handleSubmit = async (e) => {
    e.preventDefault();

    const user = {
      email: email,
      password: password,
    };

    try {
      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/auth/login.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify(user),
        }
      );

      const data = await response.json();

      if (data.status === "success") {
        setMessage("Login successful");
        sessionStorage.setItem("user", JSON.stringify(data.user));
        navigate("/");
      } else {
        setMessage(data.message || "Invalid credentials");
      }
    } catch (error) {
      console.error("Fetch error:", error);
      setMessage("Something went wrong");
    }
  };

  return (
    <div className="login">
      <form onSubmit={handleSubmit}>
        <h1>Sign in</h1>

        <label>Email</label>
        <input
          name="email"
          type="email"
          placeholder="you@example.com"
          value={email}
          onChange={(e) => setEmail(e.target.value)}
        />

        <label>Password</label>
        <input
          name="password"
          type="password"
          value={password}
          onChange={(e) => setPassword(e.target.value)}
        />

        <button type="submit">Login</button>

        {message && <p className="error">{message}</p>}
      </form>
    </div>
  );
}

export default Login;
