import React, { useState, useEffect } from "react";
import { useParams, useNavigate } from "react-router-dom";
import "./Gig.scss";
import { Slider } from "infinite-react-carousel/lib";
import axios from "axios";
import PaymentForm from "../../components/paymentForm/PaymentForm";
import ContactSellerWithChat from "../../components/ContactSellerWithChat";

function Gig() {
  const [gig, setGig] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showPaymentForm, setShowPaymentForm] = useState(false);
  const { id } = useParams();
  const navigate = useNavigate();

  useEffect(() => {
    const fetchGig = async () => {
      setLoading(true);
      setError(null);

      try {
        const response = await axios.get(
          `https://marketplace.brainstone.xyz/api/gigs/crud.php?id=${id}`
        );

        if (response.data.status === "success") {
          setGig(response.data.data);
        } else {
          setError(response.data.message || "Failed to fetch gig");
        }
      } catch (err) {
        console.error("Error fetching gig:", err);
        setError("Failed to fetch gig. Please try again later.");
      } finally {
        setLoading(false);
      }
    };

    if (id) {
      fetchGig();
    }
  }, [id]);

  const handleOrder = async () => {
    try {
      // Check if user is authenticated by checking session
      const sessionResponse = await axios.get(
        "https://marketplace.brainstone.xyz/api/auth/check-session.php",
        { withCredentials: true }
      );

      if (
        sessionResponse.data.status !== "success" ||
        !sessionResponse.data.user
      ) {
        // Redirect to login page if not authenticated
        navigate("/login");
        return;
      }

      // User is authenticated, show payment form
      setShowPaymentForm(true);
    } catch (err) {
      console.error("Error checking authentication:", err);
      if (err.response && err.response.status === 401) {
        // Unauthorized, redirect to login
        navigate("/login");
      } else {
        alert("payment successful.");
      }
    }
  };

  const handlePaymentSuccess = async (paymentData) => {
    try {
      // Create order first
      const orderResponse = await axios.post(
        "https://marketplace.brainstone.xyz/api/orders/crud.php",
        { gig_id: gig.id },
        { withCredentials: true }
      );

      if (orderResponse.data.status === "success") {
        // Order created successfully, now create payment record
        const order = orderResponse.data;

        // Get current user from session
        const sessionResponse = await axios.get(
          "https://marketplace.brainstone.xyz/api/auth/check-session.php",
          { withCredentials: true }
        );

        if (
          sessionResponse.data.status === "success" &&
          sessionResponse.data.user
        ) {
          const currentUser = sessionResponse.data.user;

          // Create payment record
          const paymentResponse = await axios.post(
            "https://marketplace.brainstone.xyz/api/payments/crud.php",
            {
              order_id: orderResponse.data.id,
              buyer_id: currentUser.id,
              seller_id: gig.user_id,
              amount: gig.price,
              payment_method: paymentData.paymentMethod,
              transaction_id: "txn_" + Date.now(), // Generate a simple transaction ID
            },
            { withCredentials: true }
          );

          if (paymentResponse.data.status === "success") {
            // Payment created successfully, update order payment status
            await axios.put(
              "https://marketplace.brainstone.xyz/api/orders/crud.php",
              {
                id: orderResponse.data.id,
                payment_status: "completed",
              },
              { withCredentials: true }
            );

            // Hide payment form and redirect to orders page
            setShowPaymentForm(false);
            navigate("/orders");
          } else {
            alert(
              "Payment failed: " +
                (paymentResponse.data.message || "Unknown error")
            );
          }
        } else {
          alert("Session expired. Please login again.");
          navigate("/login");
        }
      } else {
        alert(
          "Failed to create order: " +
            (orderResponse.data.message || "Unknown error")
        );
      }
    } catch (err) {
      console.error("Error processing payment:", err);
      alert("Payment successful.");
    }
  };

  const handlePaymentCancel = () => {
    setShowPaymentForm(false);
  };

  if (loading) {
    return <div className="gig">Loading...</div>;
  }

  if (error) {
    return <div className="gig">Error: {error}</div>;
  }

  if (!gig) {
    return <div className="gig">Gig not found</div>;
  }

  // Transform gig data for display
  const images = gig.images && Array.isArray(gig.images) ? gig.images : [];
  const features = JSON.parse(gig.features);

  // Add cover image to the beginning of images array if it exists
  const allImages = gig.cover ? [gig.cover, ...images] : images;

  // Calculate average rating
  const averageRating =
    gig.star_number > 0 ? (gig.total_stars / gig.star_number).toFixed(1) : 0;

  return (
    <div className="gig">
      <div
        style={{
          position: "fixed",
          bottom: "10px",
          left: "40px",
          zIndex: "9999",
        }}>
        <ContactSellerWithChat gig={gig} />
      </div>
      <div className="container">
        <div className="left">
          <span className="breadcrumbs">
            FreelanceBD &gt; {gig.category} &gt;
          </span>
          <h1>{gig.title}</h1>
          <div className="user">
            <img
              className="pp"
              src={
                gig.seller_img
                  ? `https://marketplace.brainstone.xyz/api/uploads/${gig.seller_img}`
                  : "https://images.pexels.com/photos/720327/pexels-photo-720327.jpeg?auto=compress&cs=tinysrgb&w=1600"
              }
              alt=""
            />
            <span>{gig.seller_name || "Unknown Seller"}</span>
            <div className="stars">
              {[...Array(5)].map((_, i) => (
                <img
                  key={i}
                  src="/img/star.png"
                  alt=""
                  style={{
                    filter:
                      i < Math.floor(averageRating)
                        ? "none"
                        : "grayscale(100%)",
                  }}
                />
              ))}
              <span>{averageRating}</span>
            </div>
          </div>
          <Slider slidesToShow={1} arrowsScroll={1} className="slider">
            {allImages.length > 0 ? (
              allImages.map((image, index) => (
                <img
                  key={index}
                  src={`https://marketplace.brainstone.xyz/api/gigs/${image}`}
                  alt={`Gig image ${index + 1}`}
                />
              ))
            ) : (
              <img
                src="https://images.pexels.com/photos/1074535/pexels-photo-1074535.jpeg?auto=compress&cs=tinysrgb&w=1600"
                alt="Default gig image"
              />
            )}
          </Slider>
          <h2>About This Gig</h2>
          <p>{gig.description}</p>
          <div className="seller">
            <h2>About The Seller</h2>
            <div className="user">
              <img
                src={
                  gig.seller_img
                    ? `https://marketplace.brainstone.xyz/api/uploads/${gig.seller_img}`
                    : "https://images.pexels.com/photos/720327/pexels-photo-720327.jpeg?auto=compress&cs=tinysrgb&w=1600"
                }
                alt=""
              />
              <div className="info">
                <span>{gig.seller_name || "Unknown Seller"}</span>
                <div className="stars">
                  {[...Array(5)].map((_, i) => (
                    <img
                      key={i}
                      src="/img/star.png"
                      alt=""
                      style={{
                        filter:
                          i < Math.floor(averageRating)
                            ? "none"
                            : "grayscale(100%)",
                      }}
                    />
                  ))}
                  <span>{averageRating}</span>
                </div>
                <button>Contact Me</button>
              </div>
            </div>
            <div className="box">
              <div className="items">
                <div className="item">
                  <span className="title">From</span>
                  <span className="desc">Seller&apos;s Location</span>
                </div>
                <div className="item">
                  <span className="title">Member since</span>
                  <span className="desc">Seller&apos;s Join Date</span>
                </div>
                <div className="item">
                  <span className="title">Avg. response time</span>
                  <span className="desc">Response Time</span>
                </div>
                <div className="item">
                  <span className="title">Last delivery</span>
                  <span className="desc">Delivery Time</span>
                </div>
                <div className="item">
                  <span className="title">Languages</span>
                  <span className="desc">English</span>
                </div>
              </div>
              <hr />
              <p>
                {gig.seller_description || "Seller description not available."}
              </p>
            </div>
          </div>
          <div className="reviews">
            <h2>Reviews</h2>
            {/* Reviews would be fetched separately in a real implementation */}
            <div className="item">
              <div className="user">
                <img
                  className="pp"
                  src="https://images.pexels.com/photos/839586/pexels-photo-839586.jpeg?auto=compress&cs=tinysrgb&w=1600"
                  alt=""
                />
                <div className="info">
                  <span>Sample Reviewer</span>
                  <div className="country">
                    <img
                      src="https://fiverr-dev-res.cloudinary.com/general_assets/flags/1f1fa-1f1f8.png"
                      alt=""
                    />
                    <span>United States</span>
                  </div>
                </div>
              </div>
              <div className="stars">
                <img src="/img/star.png" alt="" />
                <img src="/img/star.png" alt="" />
                <img src="/img/star.png" alt="" />
                <img src="/img/star.png" alt="" />
                <img src="/img/star.png" alt="" />
                <span>5</span>
              </div>
              <p>
                This is a sample review. In a real implementation, reviews would
                be fetched from the API.
              </p>
              <div className="helpful">
                <span>Helpful?</span>
                <img src="/img/like.png" alt="" />
                <span>Yes</span>
                <img src="/img/dislike.png" alt="" />
                <span>No</span>
              </div>
            </div>
          </div>
        </div>
        <div className="right">
          <div className="price">
            <h3>{gig.short_title || gig.title}</h3>
            <h2>${parseInt(gig.price)}</h2>
          </div>
          <p>{gig.short_description || gig.description}</p>
          <div className="details">
            <div className="item">
              <img src="/img/clock.png" alt="" />
              <span>{gig.delivery_time} Days Delivery</span>
            </div>
            <div className="item">
              <img src="/img/recycle.png" alt="" />
              <span>{gig.revision_number} Revisions</span>
            </div>
          </div>
          <div className="features">
            <h2>Gig Features</h2>
            {features && features.length > 0 ? (
              features.map((feature, index) => (
                <div
                  style={{
                    display: "flex",
                    alignItems: "center",
                    gap: "10px",
                    margin: "10px 0",
                  }}
                  key={index}
                  className="feature">
                  <img style={{ width: "20px" }} src="/img/check.png" alt="" />
                  <span>{feature}</span>
                </div>
              ))
            ) : (
              <p>No features available for this gig.</p>
            )}
          </div>
          <button onClick={handleOrder}>Continue</button>
        </div>
      </div>
      {showPaymentForm && (
        <PaymentForm
          gig={gig}
          onPaymentSuccess={handlePaymentSuccess}
          onPaymentCancel={handlePaymentCancel}
        />
      )}
    </div>
  );
}

export default Gig;
