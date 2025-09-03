// src/pages/SingleOrder.jsx
import React, { useEffect, useState, useRef } from "react";
import { useParams, useNavigate } from "react-router-dom";
import "./SingleOrder.scss";

const SingleOrder = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [order, setOrder] = useState(null);
  const [me, setMe] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  // Delivery form state
  // const [message, setMessage] = useState("");
  // const [file, setFile] = useState(null);
  // const [submitting, setSubmitting] = useState(false);
  // const [submitMsg, setSubmitMsg] = useState(null);
  const [summary, setSummary] = useState("");
  const [files, setFiles] = useState([]);

  // Modal state for Submit Orders
  const [isModalOpen, setIsModalOpen] = useState(false);

  // Countdown
  const [timeLeft, setTimeLeft] = useState("");
  const intervalRef = useRef(null);

  useEffect(() => {
    const fetchData = async () => {
      try {
        setLoading(true);
        // Fetch current user
        const meRes = await fetch("/api/auth/me.php", {
          credentials: "include",
        });
        if (meRes.ok) {
          const meData = await meRes.json();
          if (meData.status === "success") setMe(meData.user);
        }

        // Fetch order details
        const res = await fetch(
          `https://marketplace.brainstone.xyz/api/orders/crud.php?id=${id}`,
          {
            credentials: "include",
          }
        );
        if (!res.ok) throw new Error(`HTTP error ${res.status}`);
        const json = await res.json();
        if (json.status !== "success")
          throw new Error(json.message || "Order not found");

        setOrder(json.data);
        setLoading(false);
      } catch (e) {
        setError(e.message);
        setLoading(false);
      }
    };

    fetchData();
    return () => clearInterval(intervalRef.current);
  }, [id]);

  useEffect(() => {
    if (!order) return;

    const updateCountdown = () => {
      const now = new Date();
      const deliveryDate = new Date(
        order.delivery_date || order.delivery_at || order.delivery_time
      );
      const diff = deliveryDate - now;
      if (isNaN(deliveryDate)) {
        setTimeLeft("No delivery date");
        return;
      }
      if (diff <= 0) {
        const overdueMs = Math.abs(diff);
        setTimeLeft(formatDuration(overdueMs, true));
        return;
      }
      setTimeLeft(formatDuration(diff, false));
    };

    updateCountdown();
    intervalRef.current = setInterval(updateCountdown, 1000);
    return () => clearInterval(intervalRef.current);
  }, [order]);

  const formatDuration = (ms, past = false) => {
    const totalSec = Math.floor(ms / 1000);
    const days = Math.floor(totalSec / 86400);
    const hours = Math.floor((totalSec % 86400) / 3600);
    const mins = Math.floor((totalSec % 3600) / 60);
    const secs = totalSec % 60;
    const prefix = past ? "Past due by " : "";
    const parts = [];
    if (days) parts.push(`${days}d`);
    if (hours) parts.push(`${hours}h`);
    if (mins || (!days && !hours)) parts.push(`${mins}m`);
    parts.push(`${secs}s`);
    return prefix + parts.join(" ");
  };

  // const handleDeliverySubmit = async e => {
  //   e.preventDefault();
  //   if (!file && message.trim() === "") {
  //     setSubmitMsg("Please attach a file or write a message.");
  //     return;
  //   }

  //   setSubmitting(true);
  //   setSubmitMsg(null);

  //   try {
  //     const fd = new FormData();
  //     fd.append("order_id", order.id);
  //     fd.append("message", message);
  //     if (file) fd.append("file", file);

  //     const res = await fetch("/api/orders/submit_delivery.php", {
  //       method: "POST",
  //       credentials: "include",
  //       body: fd,
  //     });

  //     const json = await res.json();
  //     if (!res.ok || json.status !== "success") {
  //       throw new Error(json.message || `Upload failed (${res.status})`);
  //     }

  //     setSubmitMsg("Delivery submitted successfully.");
  //   } catch (err) {
  //     setSubmitMsg(err.message);
  //   } finally {
  //     setSubmitting(false);
  //   }
  // };

  // Handle Submit Orders Modal Submit
  const handleSubmit = async () => {
    const formData = new FormData();
    formData.append("summary", summary);
    formData.append("order_id", order.id); // ✅ user id যোগ

    for (let i = 0; i < files.length; i++) {
      formData.append("files[]", files[i]);
    }

    try {
      const res = await fetch(
        "https://marketplace.brainstone.xyz/api/orders/upload_order.php",
        {
          method: "POST",
          body: formData,
          credentials: "include",
        }
      );

      const data = await res.json();
      console.log(data);
      alert(data.message);
      setIsModalOpen(false);
    } catch (error) {
      console.error(error);
      alert("Upload failed");
    }
  };

  if (loading) return <div className="single-order">Loading...</div>;
  if (error) return <div className="single-order error">Error: {error}</div>;
  if (!order) return <div className="single-order">No order data</div>;
  const handleFileChange = (e) => {
    setFiles(e.target.files);
  };

  return (
    <div className="single-order">
      <div className="order-header">
        <div className="left">
          <h1>Order #{order.id}</h1>
          <p className="meta">
            Status: <strong>{order.status}</strong> • Created:{" "}
            {formatDate(order.created_at || order.order_date)}
          </p>
          <p className="meta">
            Delivery date:{" "}
            <strong>
              {formatDate(
                order.delivery_date || order.delivery_at || order.delivery_time
              )}
            </strong>
          </p>
        </div>

        <div className="right">
          <div className="countdown">
            <label>Time left / status</label>
            <div className="time-left">{timeLeft}</div>
          </div>

          <button className="btn back" onClick={() => navigate(-1)}>
            Back
          </button>

          <button
            style={{ marginLeft: "9px" }}
            className="btn primary"
            onClick={() => setIsModalOpen(true)}>
            Submit Orders
          </button>
        </div>
      </div>

      {/* Modal */}
      {isModalOpen && (
        <div className="modal-overlay">
          <div className="modal">
            <h3>Submit Order Summary & Files</h3>

            <textarea
              placeholder="Write order summary..."
              value={summary}
              onChange={(e) => setSummary(e.target.value)}
            />

            <input type="file" multiple onChange={handleFileChange} />

            <div className="modal-actions">
              <button className="btn primary" onClick={handleSubmit}>
                Submit
              </button>
              <button className="btn" onClick={() => setIsModalOpen(false)}>
                Cancel
              </button>
            </div>
          </div>
        </div>
      )}

      <div className="order-body">
        <div className="gig-card">
          <img
            src={
              order.gig_image
                ? `https://marketplace.brainstone.xyz/api/gigs/${order.gig_image}`
                : "/images/placeholder.png"
            }
            alt={order.gig_title}
          />
          <div className="gig-info">
            <h2>{order.gig_title}</h2>
            <p className="price">
              Price:{" "}
              <strong>{order.total ?? order.price ?? order.gig_price} ৳</strong>
            </p>
            <p className="overview">
              {order.gig_overview ??
                order.description ??
                "No overview provided."}
            </p>

            <div className="participants">
              <div className="user">
                <div>
                  <div className="name">{order.buyer_name}</div>
                  <div className="role">Buyer</div>
                </div>
              </div>

              <div className="user">
                <div>
                  <div className="name">{order.seller_name}</div>
                  <div className="role">Seller</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div className="order-details">
          <h3>Order Details</h3>
          <table>
            <tbody>
              <tr>
                <td>Order ID</td>
                <td>{order.id}</td>
              </tr>
              <tr>
                <td>Status</td>
                <td>{order.status}</td>
              </tr>
              <tr>
                <td>Quantity</td>
                <td>{order.quantity ?? 1}</td>
              </tr>
              <tr>
                <td>Total</td>
                <td>{order.total ?? order.price} ৳</td>
              </tr>
              <tr>
                <td>Instructions</td>
                <td>{order.instructions ?? "—"}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};

// helper
function formatDate(dateStr) {
  if (!dateStr) return "—";
  const d = new Date(dateStr);
  if (isNaN(d)) return dateStr;
  return d.toLocaleString();
}

export default SingleOrder;
