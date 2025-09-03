import React, { useState, useEffect } from "react";
import { Link, useSearchParams } from "react-router-dom";
import { usePaginatedApi, useApiSubmit } from "../../hooks/useApi";
import { useAuth } from "../../hooks/useApi";
import { messagesApi } from "../../services/api";
import "./Messages.scss";

const Messages = () => {
  const { user, isAuthenticated, loading: authLoading } = useAuth();
  const [searchParams] = useSearchParams();
  const [selectedConversation, setSelectedConversation] = useState(null);
  const [showNewMessageModal, setShowNewMessageModal] = useState(false);
  const [searchQuery, setSearchQuery] = useState('');

  // Check if we need to start a conversation with a specific user
  const targetUserId = searchParams.get('user');

  // Use paginated API hook for conversations
  const {
    data: conversations,
    pagination,
    loading,
    error,
    fetchData,
    updateParams
  } = usePaginatedApi(messagesApi.getConversations, {
    page: 1,
    limit: 20,
    search: searchQuery
  });

  // API hook for marking messages as read
  const { submit: markAsRead, loading: marking } = useApiSubmit(messagesApi.markAsRead);

  // API hook for starting new conversation
  const { submit: startConversation, loading: starting } = useApiSubmit(messagesApi.startConversation);

  // Update search when query changes
  useEffect(() => {
    if (isAuthenticated) {
      const timeoutId = setTimeout(() => {
        updateParams({ search: searchQuery });
      }, 500);
      return () => clearTimeout(timeoutId);
    }
  }, [searchQuery, isAuthenticated, updateParams]);

  // Handle starting conversation with target user
  useEffect(() => {
    if (targetUserId && isAuthenticated && !starting) {
      handleStartConversation(parseInt(targetUserId));
    }
  }, [targetUserId, isAuthenticated]);

  // Handle marking conversation as read
  const handleMarkAsRead = async (conversationId) => {
    try {
      await markAsRead({ conversation_id: conversationId });
      fetchData(); // Refresh the list
    } catch (error) {
      alert('Failed to mark as read: ' + error.message);
    }
  };

  // Handle starting new conversation
  const handleStartConversation = async (otherUserId, message = '') => {
    try {
      const result = await startConversation({
        other_user_id: otherUserId,
        message: message || 'Hello! I\'d like to discuss your services.'
      });
      
      if (result && result.conversation_id) {
        // Navigate to the conversation
        window.location.href = `/message/${result.conversation_id}`;
      }
    } catch (error) {
      alert('Failed to start conversation: ' + error.message);
    }
  };

  // Format relative time
  const formatRelativeTime = (dateString) => {
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins} min ago`;
    if (diffHours < 24) return `${diffHours} hour${diffHours > 1 ? 's' : ''} ago`;
    if (diffDays < 7) return `${diffDays} day${diffDays > 1 ? 's' : ''} ago`;
    return date.toLocaleDateString();
  };

  if (authLoading) {
    return (
      <div className="messages">
        <div className="container">
          <div className="loading">Loading...</div>
        </div>
      </div>
    );
  }

  if (!isAuthenticated) {
    return (
      <div className="messages">
        <div className="container">
          <div className="auth-required">
            <p>Please log in to view your messages.</p>
            <Link to="/login" className="login-btn">Login</Link>
          </div>
        </div>
      </div>
    );
  }

  if (loading && !conversations) {
    return (
      <div className="messages">
        <div className="container">
          <div className="loading">Loading conversations...</div>
        </div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="messages">
        <div className="container">
          <div className="error">
            <p>Error loading conversations: {error.message}</p>
            <button onClick={() => fetchData()}>Retry</button>
          </div>
        </div>
      </div>
    );
  }

  return (
    <div className="messages">
      <div className="container">
        <div className="header">
          <div className="title">
            <h1>Messages</h1>
            <p>Manage your conversations</p>
          </div>
          
          <div className="actions">
            <div className="search-box">
              <input
                type="text"
                placeholder="Search conversations..."
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                className="search-input"
              />
            </div>
            <button 
              className="new-message-btn"
              onClick={() => setShowNewMessageModal(true)}
            >
              + New Message
            </button>
          </div>
        </div>
        {conversations && conversations.length > 0 ? (
          <div className="conversations-table">
            <table>
              <thead>
                <tr>
                  <th>Contact</th>
                  <th>Last Message</th>
                  <th>Date</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                {conversations.map(conversation => (
                  <tr 
                    key={conversation.id} 
                    className={conversation.unread_count > 0 ? 'unread' : ''}
                  >
                    <td>
                      <div className="contact-info">
                        <img
                          src={conversation.other_user_img || '/img/noavatar.jpg'}
                          alt={conversation.other_user_name}
                          className="contact-avatar"
                          onError={(e) => { e.target.src = '/img/noavatar.jpg'; }}
                        />
                        <div className="contact-details">
                          <h4>{conversation.other_user_name}</h4>
                          <p className="contact-role">{conversation.other_user_role}</p>
                        </div>
                      </div>
                    </td>
                    <td>
                      <Link 
                        to={`/message/${conversation.id}`} 
                        className="message-preview"
                      >
                        <span className="message-text">
                          {conversation.last_message 
                            ? (conversation.last_message.length > 80 
                                ? conversation.last_message.substring(0, 80) + '...' 
                                : conversation.last_message)
                            : 'No messages yet'
                          }
                        </span>
                        {conversation.unread_count > 0 && (
                          <span className="unread-badge">
                            {conversation.unread_count}
                          </span>
                        )}
                      </Link>
                    </td>
                    <td className="date">
                      {formatRelativeTime(conversation.updated_at)}
                    </td>
                    <td>
                      <span className={`status-indicator ${conversation.unread_count > 0 ? 'unread' : 'read'}`}>
                        {conversation.unread_count > 0 ? 'Unread' : 'Read'}
                      </span>
                    </td>
                    <td>
                      <div className="actions">
                        <Link 
                          to={`/message/${conversation.id}`}
                          className="view-btn"
                          title="View Conversation"
                        >
                          👁️
                        </Link>
                        {conversation.unread_count > 0 && (
                          <button
                            className="mark-read-btn"
                            onClick={() => handleMarkAsRead(conversation.id)}
                            disabled={marking}
                            title="Mark as Read"
                          >
                            ✓
                          </button>
                        )}
                      </div>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            
            {/* Pagination */}
            {pagination && pagination.pages > 1 && (
              <div className="pagination">
                <button 
                  onClick={() => updateParams({ page: pagination.page - 1 })}
                  disabled={pagination.page === 1 || loading}
                >
                  Previous
                </button>
                
                <span className="page-info">
                  Page {pagination.page} of {pagination.pages}
                </span>
                
                <button 
                  onClick={() => updateParams({ page: pagination.page + 1 })}
                  disabled={pagination.page === pagination.pages || loading}
                >
                  Next
                </button>
              </div>
            )}
          </div>
        ) : (
          <div className="no-conversations">
            <div className="empty-state">
              <h3>No conversations yet</h3>
              <p>Start a conversation by browsing gigs or contacting other users.</p>
              <Link to="/gigs" className="browse-btn">
                Browse Gigs
              </Link>
            </div>
          </div>
        )}
        
        {/* New Message Modal */}
        {showNewMessageModal && (
          <NewMessageModal
            onStart={handleStartConversation}
            onClose={() => setShowNewMessageModal(false)}
            loading={starting}
          />
        )}
      </div>
    </div>
  );
};

// New Message Modal Component
const NewMessageModal = ({ onStart, onClose, loading }) => {
  const [formData, setFormData] = useState({
    other_user_id: '',
    message: ''
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    if (formData.other_user_id && formData.message) {
      onStart(parseInt(formData.other_user_id), formData.message);
    }
  };

  const handleChange = (e) => {
    setFormData({
      ...formData,
      [e.target.name]: e.target.value
    });
  };

  return (
    <div className="modal-overlay">
      <div className="modal">
        <div className="modal-header">
          <h3>Start New Conversation</h3>
          <button className="close-btn" onClick={onClose}>&times;</button>
        </div>
        
        <form onSubmit={handleSubmit} className="modal-body">
          <div className="form-group">
            <label>User ID:</label>
            <input
              type="number"
              name="other_user_id"
              value={formData.other_user_id}
              onChange={handleChange}
              placeholder="Enter user ID to message"
              required
            />
            <small>You can find user IDs on their profile pages or gig listings.</small>
          </div>
          
          <div className="form-group">
            <label>Initial Message:</label>
            <textarea
              name="message"
              value={formData.message}
              onChange={handleChange}
              placeholder="Type your message here..."
              rows="4"
              required
            />
          </div>
          
          <div className="modal-footer">
            <button type="button" onClick={onClose} disabled={loading}>
              Cancel
            </button>
            <button type="submit" disabled={loading || !formData.other_user_id || !formData.message}>
              {loading ? 'Starting...' : 'Start Conversation'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default Messages;
