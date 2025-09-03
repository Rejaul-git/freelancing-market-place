import React, { useState } from "react";
import "./PaymentForm.scss";

const PaymentForm = ({ gig, onPaymentSuccess, onPaymentCancel }) => {
  const [paymentData, setPaymentData] = useState({
    cardNumber: "",
    cardName: "",
    expiryDate: "",
    cvv: "",
    paymentMethod: "credit_card",
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState("");

  const handleChange = (e) => {
    const { name, value } = e.target;
    setPaymentData((prev) => ({
      ...prev,
      [name]: value,
    }));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError("");

    try {
      // In a real implementation, this would process the payment through a payment gateway
      // For now, we'll simulate a successful payment
      await new Promise((resolve) => setTimeout(resolve, 1500));

      // Call the success callback with payment data
      onPaymentSuccess({
        ...paymentData,
        amount: gig.price,
        gigId: gig.id,
      });
    } catch (err) {
      setError("Payment failed. Please try again.");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="payment-form-overlay">
      <div className="payment-form">
        <div className="payment-form-header">
          <h2>Complete Your Payment</h2>
          <button className="close-btn" onClick={onPaymentCancel}>
            &times;
          </button>
        </div>

        <div className="order-summary">
          <h3>Order Summary</h3>
          <div className="gig-info">
            <img
              src={
                gig.cover
                  ? `https://marketplace.brainstone.xyz/api/gigs/${gig.cover}`
                  : "/img/noimage.png"
              }
              alt={gig.title}
              onError={(e) => {
                e.target.src = "/img/noimage.png";
              }}
            />
            <div className="gig-details">
              <h4>{gig.title}</h4>
              <p>By {gig.seller_name || "Unknown Seller"}</p>
            </div>
          </div>
          <div className="price-details">
            <div className="price-row">
              <span>Price:</span>
              <span>${gig.price}</span>
            </div>
            <div className="price-row">
              <span>Service Fee:</span>
              <span>${(gig.price * 0.05).toFixed(2)}</span>
            </div>
            <div className="price-row total">
              <span>Total:</span>
              <span>${(gig.price * 1.05).toFixed(2)}</span>
            </div>
          </div>
        </div>

        <form onSubmit={handleSubmit} className="payment-details">
          <h3>Payment Details</h3>

          <div className="form-group">
            <label>Payment Method</label>
            <select
              name="paymentMethod"
              value={paymentData.paymentMethod}
              onChange={handleChange}
              required>
              <option value="credit_card">Credit Card</option>
              <option value="debit_card">Debit Card</option>
              <option value="paypal">PayPal</option>
              <option value="bank_transfer">Bank Transfer</option>
            </select>
          </div>

          {(paymentData.paymentMethod === "credit_card" ||
            paymentData.paymentMethod === "debit_card") && (
            <>
              <div className="form-group">
                <label>Card Number</label>
                <input
                  type="text"
                  name="cardNumber"
                  value={paymentData.cardNumber}
                  onChange={handleChange}
                  placeholder="1234 5678 9012 3456"
                  required
                />
              </div>

              <div className="form-group">
                <label>Name on Card</label>
                <input
                  type="text"
                  name="cardName"
                  value={paymentData.cardName}
                  onChange={handleChange}
                  placeholder="John Doe"
                  required
                />
              </div>

              <div className="form-row">
                <div className="form-group">
                  <label>Expiry Date</label>
                  <input
                    type="text"
                    name="expiryDate"
                    value={paymentData.expiryDate}
                    onChange={handleChange}
                    placeholder="MM/YY"
                    required
                  />
                </div>

                <div className="form-group">
                  <label>CVV</label>
                  <input
                    type="text"
                    name="cvv"
                    value={paymentData.cvv}
                    onChange={handleChange}
                    placeholder="123"
                    required
                  />
                </div>
              </div>
            </>
          )}

          {error && <div className="error-message">{error}</div>}

          <div className="form-actions">
            <button
              type="button"
              onClick={onPaymentCancel}
              disabled={loading}
              className="cancel-btn">
              Cancel
            </button>
            <button type="submit" disabled={loading} className="pay-btn">
              {loading
                ? "Processing..."
                : `Pay $${(gig.price * 1.05).toFixed(2)}`}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default PaymentForm;
