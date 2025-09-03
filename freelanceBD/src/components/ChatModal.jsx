// // import React, { useState, useEffect } from "react";
// // import { useApiSubmit } from "../hooks/useApi";
// // import { specializedApi } from "../services/api";

// // const ChatModal = ({ gig, onClose }) => {
// //   const [message, setMessage] = useState("");
// //   const {
// //     submit: startConversation,
// //     loading,
// //     error,
// //   } = useApiSubmit(specializedApi.startConversation);

// //   const handleSubmit = async (e) => {
// //     e.preventDefault();

// //     if (!message.trim()) {
// //       // Instead of alert, we could show an inline error message
// //       return;
// //     }

// //     try {
// //       const result = await startConversation({
// //         recipient_id: gig.user_id,
// //         gig_id: gig.id,
// //         message: message.trim(),
// //       });

// //       if (result && result.status === "success") {
// //         // Close the modal and optionally show a success message
// //         setMessage("");
// //         onClose();

// //         // Redirect to messages page to continue conversation
// //         window.location.href = "/messages";
// //       }
// //     } catch (err) {
// //       console.error("Failed to send message:", err);
// //       // Error will be displayed through the error state in useApiSubmit
// //     }
// //   };

// //   // Handle Enter key for sending message (without Shift)
// //   const handleKeyDown = (e) => {
// //     if (e.key === "Enter" && !e.shiftKey) {
// //       e.preventDefault();
// //       handleSubmit(e);
// //     }
// //   };

// //   return (
// //     <div className="messenger-overlay" onClick={onClose}>
// //       <div className="messenger-modal" onClick={(e) => e.stopPropagation()}>
// //         <div className="messenger-header">
// //           <div className="messenger-header-left">
// //             <div className="messenger-user-avatar">
// //               <img
// //                 src={
// //                   gig.seller_img
// //                     ? `https://marketplace.brainstone.xyz/api/uploads/${gig.seller_img}`
// //                     : "/img/noavatar.jpg"
// //                 }
// //                 alt={gig.seller_name}
// //                 onError={(e) => (e.target.src = "/img/noavatar.jpg")}
// //               />
// //               <div className="avatar-status"></div>
// //             </div>
// //             <div className="messenger-user-info">
// //               <h3>Contact {gig.seller_name}</h3>
// //               <span className="user-status online">Online</span>
// //             </div>
// //           </div>
// //           <div className="messenger-header-right">
// //             <button className="messenger-close-btn" onClick={onClose}>
// //               &times;
// //             </button>
// //           </div>
// //         </div>

// //         <div className="messenger-body">
// //           <div className="gig-preview">
// //             <img
// //               src={
// //                 gig.cover
// //                   ? `https://marketplace.brainstone.xyz/api/gigs/${gig.cover}`
// //                   : "/img/noavatar.jpg"
// //               }
// //               alt={gig.title}
// //               className="gig-image"
// //               onError={(e) => (e.target.src = "/img/noavatar.jpg")}
// //             />
// //             <div className="gig-details">
// //               <h4>{gig.title}</h4>
// //               <p className="price">${gig.price}</p>
// //             </div>
// //           </div>

// //           <div className="messenger-message-input-area">
// //             <form onSubmit={handleSubmit} className="messenger-message-form">
// //               <div className="messenger-input-group">
// //                 <textarea
// //                   value={message}
// //                   onChange={(e) => setMessage(e.target.value)}
// //                   onKeyDown={handleKeyDown}
// //                   placeholder="Type your message here..."
// //                   rows="3"
// //                   required
// //                   disabled={loading}
// //                 />
// //                 <button
// //                   type="submit"
// //                   disabled={loading || !message.trim()}
// //                   className="messenger-send-button">
// //                   {loading ? (
// //                     <div
// //                       className="messenger-spinner"
// //                       style={{
// //                         width: "20px",
// //                         height: "20px",
// //                         border: "2px solid #ffffff40",
// //                         borderTop: "2px solid #fff",
// //                       }}></div>
// //                   ) : (
// //                     <svg viewBox="0 0 24 24" fill="currentColor">
// //                       <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
// //                     </svg>
// //                   )}
// //                 </button>
// //               </div>

// //               {error && (
// //                 <div className="messenger-error-message">
// //                   {error.message || "Failed to send message. Please try again."}
// //                 </div>
// //               )}
// //             </form>
// //           </div>
// //         </div>
// //       </div>
// //     </div>
// //   );
// // };

// // export default ChatModal;
// // ChatModal.jsx
// import React, { useState } from "react";
// import "./ChatModal.scss"; // তোমার SCSS ফাইল

// const ChatModal = ({ gig, onClose }) => {
//   const [message, setMessage] = useState("");
//   const [loading, setLoading] = useState(false);
//   const [error, setError] = useState("");

//   const handleSubmit = async (e) => {
//     e.preventDefault();

//     if (!message.trim()) return;

//     try {
//       setLoading(true);
//       setError("");

//       // এখানে তুমি API call করতে পারো
//       await new Promise((resolve) => setTimeout(resolve, 1000));

//       // success হলে modal বন্ধ এবং input clear
//       setMessage("");
//       onClose();
//       alert("Message sent successfully!");
//     } catch (err) {
//       console.error(err);
//       setError("Failed to send message. Try again.");
//     } finally {
//       setLoading(false);
//     }
//   };

//   return (
//     <div className="contact-seller__overlay" onClick={onClose}>
//       <div
//         className="contact-seller__modal"
//         onClick={(e) => e.stopPropagation()}>
//         <div className="contact-seller__modal-header">
//           <h2>Contact {gig.seller_name}</h2>
//           <button onClick={onClose}>&times;</button>
//         </div>

//         <div className="contact-seller__modal-body">
//           <div className="contact-seller__avatar">
//             <figure>
//               <img
//                 src={gig.seller_img || "/img/noavatar.jpg"}
//                 alt={gig.seller_name}
//                 onError={(e) => (e.target.src = "/img/noavatar.jpg")}
//               />
//             </figure>
//             <div className="contact-seller__avatar-details">
//               <h3>{gig.seller_name}</h3>
//               <div className="contact-seller__status">
//                 <span className="status-indicator online"></span> Online
//               </div>
//             </div>
//           </div>

//           <textarea
//             placeholder="Type your message..."
//             value={message}
//             onChange={(e) => setMessage(e.target.value)}
//             rows="4"
//             disabled={loading}
//           />

//           {error && <div className="contact-seller__error">{error}</div>}
//         </div>

//         <div className="contact-seller__modal-footer">
//           <button className="cancel" onClick={onClose} disabled={loading}>
//             Cancel
//           </button>
//           <button
//             className="send"
//             onClick={handleSubmit}
//             disabled={loading || !message.trim()}>
//             {loading ? "Sending..." : "Send"}
//           </button>
//         </div>
//       </div>
//     </div>
//   );
// };

// export default ChatModal;
