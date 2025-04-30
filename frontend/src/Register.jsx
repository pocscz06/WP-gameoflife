import React, { useState, useEffect } from "react";
import { Link, useNavigate } from "react-router-dom";
import "./styles/Auth.css";

const Register = () => {
  const navigate = useNavigate();

  const [formData, setFormData] = useState({
    username: "",
    email: "",
    password: "",
    confirmPassword: "",
  });

  const [errors, setErrors] = useState([]);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [registerSuccess, setRegisterSuccess] = useState(false);

  useEffect(() => {
    const checkLoginStatus = async () => {
      try {
        const response = await fetch(
          "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/api/user/check-session.php",
          {
            credentials: "include",
          }
        );
        const data = await response.json();

        if (data.logged_in) {
          navigate("/game");
        }
      } catch (error) {
        console.error("Error checking login status:", error);
      }
    };

    checkLoginStatus();
  }, [navigate]);

  const handleChange = (e) => {
    const { name, value } = e.target;
    setFormData({
      ...formData,
      [name]: value,
    });
  };

  const validateForm = () => {
    const newErrors = [];

    if (!formData.username.trim()) {
      newErrors.push("Username is required");
    } else if (formData.username.length < 6) {
      newErrors.push("Username must be at least 6 characters");
    }

    if (!formData.email.trim()) {
      newErrors.push("Email is required");
    } else if (!/\S+@\S+\.\S+/.test(formData.email)) {
      newErrors.push("Email is invalid");
    }

    if (!formData.password) {
      newErrors.push("Password is required");
    } else if (formData.password.length < 8) {
      newErrors.push("Password must be at least 8 characters");
    }

    if (formData.password !== formData.confirmPassword) {
      newErrors.push("Passwords do not match");
    }

    return newErrors;
  };

  const handleSubmit = async (e) => {
    e.preventDefault();

    const validationErrors = validateForm();
    if (validationErrors.length > 0) {
      setErrors(validationErrors);
      return;
    }

    setIsSubmitting(true);

    try {
      const response = await fetch(
        "https://codd.cs.gsu.edu/~kpham21/WP-gameoflife/backend/auth/register.php",
        {
          method: "POST",
          credentials: "include",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({
            username: formData.username,
            email: formData.email,
            password: formData.password,
            confirm_password: formData.confirmPassword,
          }),
        }
      );

      const data = await response.json();

      if (data.success) {
        setRegisterSuccess(true);
        setTimeout(() => {
          navigate("/login");
        }, 2000);
      } else {
        setErrors(data.errors || ["Registration failed. Please try again."]);
      }
    } catch (error) {
      console.error("Registration error:", error);
      setErrors(["An error occurred. Please try again later."]);
    } finally {
      setIsSubmitting(false);
    }
  };

  return (
    <div className="auth-container">
      <div className="auth-card">
        <h2>Register</h2>

        {registerSuccess ? (
          <div className="success-message">
            Registration successful! Redirecting to login...
          </div>
        ) : (
          <>
            {errors.length > 0 && (
              <div className="error-box">
                <ul>
                  {errors.map((error, index) => (
                    <li key={index}>{error}</li>
                  ))}
                </ul>
              </div>
            )}

            <form onSubmit={handleSubmit}>
              <div className="form-group">
                <label htmlFor="username">Username</label>
                <input
                  type="text"
                  id="username"
                  name="username"
                  value={formData.username}
                  onChange={handleChange}
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="email">Email</label>
                <input
                  type="email"
                  id="email"
                  name="email"
                  value={formData.email}
                  onChange={handleChange}
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="password">Password</label>
                <input
                  type="password"
                  id="password"
                  name="password"
                  value={formData.password}
                  onChange={handleChange}
                  required
                />
              </div>

              <div className="form-group">
                <label htmlFor="confirmPassword">Confirm Password</label>
                <input
                  type="password"
                  id="confirmPassword"
                  name="confirmPassword"
                  value={formData.confirmPassword}
                  onChange={handleChange}
                  required
                />
              </div>

              <button
                type="submit"
                className="auth-button"
                disabled={isSubmitting}
              >
                {isSubmitting ? "Registering..." : "Register"}
              </button>
            </form>

            <div className="auth-footer">
              Already have an account? <Link to="/login">Log in</Link>
            </div>
          </>
        )}
      </div>
    </div>
  );
};

export default Register;
