import React, { useState, useEffect, useRef } from "react";
import { useApi, useApiSubmit } from "../hooks/useApi";
import { specializedApi } from "../services/api";

const FacebookMessengerChat = ({ gig, onClose }) => {
  const [message, setMessage] = useState("");
  const [conversationId, setConversationId] = useState(null);
  const [messages, setMessages] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const messagesEndRef = useRef(null);
  const textareaRef = useRef(null);

  const { submit: startConversation } = useApiSubmit(
    specializedApi.startConversation
  );
  const { submit: sendMessage } = useApiSubmit(specializedApi.sendMessage);
  const { execute: getMessages } = useApi(
    specializedApi.getConversationMessages
  );

  // Auto-resize textarea
  const adjustTextareaHeight = () => {
    const textarea = textareaRef.current;
    if (textarea) {
      textarea.style.height = "auto";
      textarea.style.height = `${Math.min(textarea.scrollHeight, 100)}px`;
    }
  };

  // Scroll to bottom of messages
  const scrollToBottom = () => {
    messagesEndRef.current?.scrollIntoView({ behavior: "smooth" });
  };

  useEffect(() => {
    scrollToBottom();
  }, [messages]);

  useEffect(() => {
    adjustTextareaHeight();
  }, [message]);

  // Start conversation when component mounts
  useEffect(() => {
    const initConversation = async () => {
      setLoading(true);
      setError(null);
      try {
        const result = await startConversation({
          recipient_id: gig.user_id,
          gig_id: gig.id,
          message: "Hello! I'm interested in your gig.",
        });

        if (result && result.status === "success" && result.conversation_id) {
          setConversationId(result.conversation_id);
          // Load initial messages
          await loadMessages(result.conversation_id);
        } else {
          setError("Failed to start conversation. Please try again.");
        }
      } catch (err) {
        console.error("Failed to start conversation:", err);
        setError("Failed to start conversation. Please try again.");
      } finally {
        setLoading(false);
      }
    };

    if (gig && gig.user_id && gig.id) {
      initConversation();
    }
  }, [gig]);

  // Load messages for conversation
  const loadMessages = async (convId) => {
    try {
      const result = await getMessages({ conversation_id: convId });
      if (result && result.data) {
        setMessages(Array.isArray(result.data) ? result.data : []);
      }
    } catch (err) {
      console.error("Failed to load messages:", err);
      setError("Failed to load messages. Please try again.");
    }
  };

  // Handle sending a new message
  const handleSubmit = async (e) => {
    e.preventDefault();

    const trimmedMessage = message.trim();
    if (!trimmedMessage || !conversationId) {
      return;
    }

    // Clear the input immediately for better UX
    setMessage("");
    setError(null);

    try {
      const result = await sendMessage({
        conversation_id: conversationId,
        content: trimmedMessage,
      });

      if (result && result.status === "success") {
        // Add message to local state
        const newMessage = {
          id: result.message_id || Date.now(),
          content: trimmedMessage,
          sender_id: null, // Current user message
          created_at: new Date().toISOString(),
        };
        setMessages((prev) => [...prev, newMessage]);
      } else {
        // If failed, restore the message
        setMessage(trimmedMessage);
        setError("Failed to send message. Please try again.");
      }
    } catch (err) {
      console.error("Failed to send message:", err);
      // Restore the message on error
      setMessage(trimmedMessage);
      setError("Failed to send message. Please try again.");
    }
  };

  // Handle Enter key for sending message (without Shift)
  const handleKeyDown = (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      handleSubmit(e);
    }
  };

  // Handle input change
  const handleMessageChange = (e) => {
    setMessage(e.target.value);
  };

  // Clear error when user starts typing
  const handleFocus = () => {
    if (error) {
      setError(null);
    }
  };

  return (
    <div className="messenger-overlay" onClick={onClose}>
      <div className="messenger-modal" onClick={(e) => e.stopPropagation()}>
        {/* Header */}
        <div className="messenger-header">
          <div className="messenger-header-left">
            <div className="messenger-user-avatar">
              <img
                src={
                  gig.seller_img
                    ? `https://marketplace.brainstone.xyz/api/uploads/${gig.seller_img}`
                    : "/img/noavatar.jpg"
                }
                alt={gig.seller_name || "User"}
                onError={(e) => (e.target.src = "/img/noavatar.jpg")}
              />
              <div className="avatar-status"></div>
            </div>
            <div className="messenger-user-info">
              <h3>{gig.seller_name || "User"}</h3>
              <span className="user-status online">Online</span>
            </div>
          </div>
          <div className="messenger-header-right">
            <button
              className="messenger-close-btn"
              onClick={onClose}
              type="button">
              &times;
            </button>
          </div>
        </div>

        {/* Chat Body */}
        <div className="messenger-body">
          {/* Messages container */}
          <div className="messenger-messages-container">
            {loading && messages.length === 0 ? (
              <div className="messenger-loading-messages">
                <div className="messenger-spinner"></div>
                <p>Loading messages...</p>
              </div>
            ) : (
              <>
                {messages.length === 0 && !loading ? (
                  <div className="messenger-empty-messages">
                    <p>Start your conversation!</p>
                  </div>
                ) : (
                  messages.map((msg) => (
                    <div
                      key={msg.id}
                      className={`messenger-message ${
                        msg.sender_id == gig.user_id ? "received" : "sent"
                      }`}>
                      <div className="messenger-message-content">
                        {msg.content}
                      </div>
                      <div className="messenger-message-time">
                        {new Date(msg.created_at).toLocaleTimeString([], {
                          hour: "2-digit",
                          minute: "2-digit",
                        })}
                      </div>
                    </div>
                  ))
                )}
                <div ref={messagesEndRef} />
              </>
            )}
          </div>

          {/* Error message */}
          {error && (
            <div className="messenger-error-message">
              {error}
              <button
                onClick={() => setError(null)}
                className="error-dismiss"
                type="button">
                ×
              </button>
            </div>
          )}

          {/* Message input area */}
          <div className="messenger-message-input-area">
            <form onSubmit={handleSubmit} className="messenger-message-form">
              <div className="messenger-input-group">
                <textarea
                  ref={textareaRef}
                  value={message}
                  onChange={handleMessageChange}
                  onKeyDown={handleKeyDown}
                  onFocus={handleFocus}
                  onInput={adjustTextareaHeight}
                  placeholder="Type a message..."
                  rows="1"
                  disabled={!conversationId || loading}
                  style={{
                    overflow: "hidden",
                    resize: "none",
                  }}
                />
                <button
                  type="submit"
                  disabled={!conversationId || loading || !message.trim()}
                  className="messenger-send-button">
                  <svg
                    viewBox="0 0 24 24"
                    fill="currentColor"
                    width="20"
                    height="20">
                    <path d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"></path>
                  </svg>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  );
};

export default FacebookMessengerChat;
