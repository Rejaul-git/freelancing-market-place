// import React, { useState, useEffect, useRef } from "react";
// import { useApi, useApiSubmit } from "../hooks/useApi";
// import { specializedApi } from "../services/api";

// const ChatWindow = ({ gig, onClose }) => {
//   const [message, setMessage] = useState("");
//   const [conversationId, setConversationId] = useState(null);
//   const [messages, setMessages] = useState([]);
//   const messagesEndRef = useRef(null);

//   const { submit: startConversation, loading: starting } = useApiSubmit(specializedApi.startConversation);
//   const { submit: sendMessage, loading: sending } = useApiSubmit(specializedApi.sendMessage);
//   const { execute: getMessages } = useApi(specializedApi.getConversationMessages);

//   // Scroll to bottom of messages
//   const scrollToBottom = () => {
//     messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
//   };

//   useEffect(() => {
//     scrollToBottom();
//   }, [messages]);

//   // Start conversation when component mounts
//   useEffect(() => {
//     const initConversation = async () => {
//       try {
//         const result = await startConversation({
//           recipient_id: gig.user_id,
//           gig_id: gig.id,
//           message: "Hello! I'm interested in your gig."
//         });

//         if (result && result.status === "success" && result.conversation_id) {
//           setConversationId(result.conversation_id);
//           // Load initial messages
//           loadMessages(result.conversation_id);
//         }
//       } catch (err) {
//         console.error("Failed to start conversation:", err);
//       }
//     };

//     initConversation();
//   }, [gig]);

//   // Load messages for conversation
//   const loadMessages = async (convId) => {
//     try {
//       const result = await getMessages({ conversation_id: convId });
//       if (result && result.data) {
//         setMessages(result.data);
//       }
//     } catch (err) {
//       console.error("Failed to load messages:", err);
//     }
//   };

//   const handleSubmit = async (e) => {
//     e.preventDefault();

//     if (!message.trim() || !conversationId) {
//       return;
//     }

//     try {
//       const result = await sendMessage({
//         conversation_id: conversationId,
//         content: message.trim()
//       });

//       if (result && result.status === "success") {
//         // Add message to local state
//         const newMessage = {
//           id: result.message_id,
//           content: message.trim(),
//           sender_id: null, // Will be set by backend
//           created_at: new Date().toISOString()
//         };
//         setMessages(prev => [...prev, newMessage]);
//         setMessage("");
//       }
//     } catch (err) {
//       console.error("Failed to send message:", err);
//     }
//   };

//   // Handle Enter key for sending message (without Shift)
//   const handleKeyDown = (e) => {
//     if (e.key === 'Enter' && !e.shiftKey) {
//       e.preventDefault();
//       handleSubmit(e);
//     }
//   };

//   return (
//     <div className="messenger-overlay" onClick={onClose}>
//       <div className="messenger-modal" onClick={(e) => e.stopPropagation()}>
//         {/* Header */}
//         <div className="messenger-header">
//           <div className="messenger-header-left">
//             <div className="messenger-user-avatar">
//               <img
//                 src={gig.seller_img ? `https://marketplace.brainstone.xyz/api/uploads/${gig.seller_img}` : "/img/noavatar.jpg"}
//                 alt={gig.seller_name}
//                 onError={(e) => e.target.src = "/img/noavatar.jpg"}
//               />
//               <div className="avatar-status"></div>
//             </div>
//             <div className="messenger-user-info">
//               <h3>Chat with {gig.seller_name}</h3>
//               <span className="user-status online">Online</span>
//             </div>
//           </div>
//           <div className="messenger-header-right">
//             <button className="messenger-close-btn" onClick={onClose}>&times;</button>
//           </div>
//         </div>

//         {/* Chat Body */}
//         <div className="messenger-body">
//           {/* Messages container */}
//           <div className="messenger-messages-container">
//             {messages.map((msg) => (
//               <div
//                 key={msg.id}
//                 className={`messenger-message ${msg.sender_id == gig.user_id ? 'received' : 'sent'}`}
//               >
//                 <div className="messenger-message-content">
//                   {msg.content}
//                 </div>
//                 <div className="messenger-message-time">
//                   {new Date(msg.created_at).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
//                 </div>
//               </div>
//             ))}
//             <div ref={messagesEndRef} />
//           </div>

//           {/* Message input area */}
//           <div className="messenger-message-input-area">
//             <form onSubmit={handleSubmit} className="messenger-message-form">
//               <div className="messenger-input-group">
//                 <textarea
//                   value={message}
//                   onChange={(e) => setMessage(e.target.value)}
//                   onKeyDown={handleKeyDown}
//                   placeholder="Type your message here..."
//                   rows="1"
//                   disabled={!conversationId || starting || sending}
//                 />
//                 <button
//                   type="submit"
//                   disabled={!conversationId || starting || sending || !message.trim()}
//                   className="messenger-send-button"
//                 >
//                   {sending ? (
//                     <div className="messenger-spinner" style={{width: '20px', height: '20px', border: '2px solid #ffffff40', borderTop: '2px solid #fff'}}></div>
//                   ) : (
//                     <svg viewBox="0 0 24 24" fill="currentColor">
//                       <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
//                     </svg>
//                   )}
//                 </button>
//               </div>
//             </form>
//           </div>
//         </div>
//       </div>
//     </div>
//   );
// };

// export default ChatWindow;
