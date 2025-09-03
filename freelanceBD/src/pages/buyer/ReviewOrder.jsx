import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import "./ReviewOrder.scss";

const ReviewOrder = () => {
  const { orderId } = useParams();
  const navigate = useNavigate();
  const [order, setOrder] = useState(null);
  const [rating, setRating] = useState(5);
  const [reviewText, setReviewText] = useState("");
  const [loading, setLoading] = useState(true);
  const [submitting, setSubmitting] = useState(false);

  useEffect(() => {
    fetchOrderDetails();
  }, [orderId]);

  const fetchOrderDetails = async () => {
    try {
      const response = await fetch(
        `https://marketplace.brainstone.xyz/api/orders/crud.php?id=${orderId}`,
        {
          credentials: "include",
        }
      );
      const data = await response.json();
      if (data.status === "success") {
        setOrder(data.data);
      } else {
        console.error("Error fetching order:", data.message);
      }
    } catch (error) {
      console.error("Error fetching order:", error);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setSubmitting(true);

    try {
      const response = await fetch(
        "https://marketplace.brainstone.xyz/api/reviews/create_review.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          credentials: "include",
          body: JSON.stringify({
            order_id: orderId,
            rating: rating,
            review_text: reviewText,
          }),
        }
      );
      const data = await response.json();
      if (data.status === "success") {
        alert("Review submitted successfully!");
        navigate("/buyer/dashboard");
      } else {
        alert("Error: " + data.message);
      }
    } catch (error) {
      console.error("Error submitting review:", error);
      alert("Error submitting review");
    } finally {
      setSubmitting(false);
    }
  };

  if (loading) {
    return <div className="reviewOrder">Loading...</div>;
  }

  if (!order) {
    return <div className="reviewOrder">Order not found</div>;
  }

  return (
    <div className="reviewOrder">
      <div className="container">
        <div className="header">
          <h1>Review Order</h1>
          <button onClick={() => navigate(-1)} className="backBtn">
            ← Back
          </button>
        </div>

        <div className="orderInfo">
          <div className="gigInfo">
            <img
              src={
                "https://marketplace.brainstone.xyz/api/gigs/" +
                  order.gig_image || "/img/placeholder.jpg"
              }
              alt={order.gig_title}
            />
            <div>
              <h2>{order.gig_title}</h2>
              <p>Seller: {order.seller_name}</p>
              <p>Price: ${order.price}</p>
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="reviewForm">
          <div className="formGroup">
            <label>Rating:</label>
            <div className="ratingStars">
              {[1, 2, 3, 4, 5].map((star) => (
                <span
                  key={star}
                  className={`star ${star <= rating ? "filled" : ""}`}
                  onClick={() => setRating(star)}>
                  ★
                </span>
              ))}
            </div>
          </div>

          <div className="formGroup">
            <label htmlFor="reviewText">Review:</label>
            <textarea
              id="reviewText"
              value={reviewText}
              onChange={(e) => setReviewText(e.target.value)}
              placeholder="Share your experience with this service..."
              rows="5"
            />
          </div>

          <button type="submit" disabled={submitting} className="submitBtn">
            {submitting ? "Submitting..." : "Submit Review"}
          </button>
        </form>
      </div>
    </div>
  );
};

export default ReviewOrder;
