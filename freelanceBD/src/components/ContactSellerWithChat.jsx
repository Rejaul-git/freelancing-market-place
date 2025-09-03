import React, { useState } from "react";
import { useNavigate } from "react-router-dom";
import { useAuth } from "./../hooks/useApi";
import FacebookMessengerChat from "./FacebookMessengerChat";

const defaultAvatar = "/images/default-avatar.png"; // Fallback avatar image
import "./chat.scss"; // Import the updated SCSS file

export default function ContactSellerWithChat({ gig }) {
  const { isAuthenticated } = useAuth();
  const navigate = useNavigate();
  const [showModal, setShowModal] = useState(false);

  const handleContactClick = () => {
    if (!isAuthenticated) {
      navigate("/login");
      return;
    }
    setShowModal(true);
  };

  const handleCloseModal = () => {
    setShowModal(false);
  };

  // For now, we'll assume the seller is online
  // In a real implementation, this would come from the API
  const isSellerOnline = true;

  return (
    <>
      <div className="contact-seller" onClick={handleContactClick}>
        <div className="contact-seller__avatar">
          <figure title={gig.seller_name}>
            <img
              src={
                gig.seller_img
                  ? `https://marketplace.brainstone.xyz/api/uploads/${gig.seller_img}`
                  : defaultAvatar
              }
              alt={gig.seller_name}
              loading="lazy"
              onError={(e) => (e.target.src = defaultAvatar)}
            />
          </figure>
          <div
            className={`contact-seller__status ${
              isSellerOnline ? "online" : "offline"
            }`}></div>
        </div>

        <div className="contact-seller__info">
          <p className="contact-seller__username">Message {gig.seller_name}</p>
          <span className="contact-seller__response">
            {isSellerOnline ? "Online" : "Offline"} · Avg. response time:{" "}
            <strong>1 Hour</strong>
          </span>
        </div>
      </div>

      {showModal && (
        <FacebookMessengerChat gig={gig} onClose={handleCloseModal} />
      )}
    </>
  );
}
