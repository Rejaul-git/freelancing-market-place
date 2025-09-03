import React, { useState, useEffect } from "react";
import AdminDashboard from "../pages/admin/AdminDashboard";
import SellerDashboard from "../pages/seller/SellerDashboard";
import BuyerDashboard from "../pages/buyer/BuyerDashboard";

const DashboardRouter = () => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    checkUserSession();
  }, []);

  const checkUserSession = async () => {
    try {
      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/auth/check-session.php",
        {
          credentials: "include",
        }
      );
      const data = await response.json();
      if (data.status === "success") {
        setUser(data.user);
      }
      setLoading(false);
    } catch (error) {
      console.error("Error checking session:", error);
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div
        style={{
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          height: "100vh",
          fontSize: "1.2rem",
          color: "#666",
        }}>
        Loading dashboard...
      </div>
    );
  }

  if (!user) {
    return (
      <div
        style={{
          display: "flex",
          justifyContent: "center",
          alignItems: "center",
          height: "100vh",
          flexDirection: "column",
          gap: "20px",
        }}>
        <h2>Please log in to access your dashboard</h2>
        <a
          href="/login"
          style={{
            background: "#1dbf73",
            color: "white",
            padding: "12px 24px",
            borderRadius: "5px",
            textDecoration: "none",
          }}>
          Go to Login
        </a>
      </div>
    );
  }

  // Route to appropriate dashboard based on user role
  switch (user.role) {
    case "admin":
      return <AdminDashboard />;
    case "seller":
      return <SellerDashboard />;
    case "buyer":
      return <BuyerDashboard />;
    default:
      return (
        <div
          style={{
            display: "flex",
            justifyContent: "center",
            alignItems: "center",
            height: "100vh",
            flexDirection: "column",
            gap: "20px",
          }}>
          <h2>Invalid user role</h2>
          <p>Please contact support for assistance.</p>
        </div>
      );
  }
};

export default DashboardRouter;
