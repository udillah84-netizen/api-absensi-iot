package main

import (
	"net/http"
	"os"
	"time"

	"github.com/gin-gonic/gin"
)

type ScanRequest struct {
	Code      string `json:"code" binding:"required"`
	DeviceID  string `json:"device_id"`
	ScannedAt string `json:"scanned_at"`
	Jenis     string `json:"jenis"`
}

type HeartbeatRequest struct {
	DeviceID  string `json:"device_id" binding:"required"`
	WifiRSSI  int    `json:"wifi_rssi"`
	FwVersion string `json:"fw_version"`
}

func main() {
	secretToken := os.Getenv("IOT_SECRET_TOKEN")
	if secretToken == "" {
		secretToken = "tok_rahasia_mesin_anda"
	}

	r := gin.Default()

	// Auth Middleware
	authMiddleware := func(c *gin.Context) {
		token := c.GetHeader("X-Device-Token")
		if token == "" {
			token = c.GetHeader("X-IoT-Token")
		}
		if token != secretToken {
			c.JSON(http.StatusUnauthorized, gin.H{
				"success": false,
				"status":  "Unauthorized",
				"message": "Invalid or missing X-Device-Token",
			})
			c.Abort()
			return
		}
		c.Next()
	}

	api := r.Group("/api/v1/iot")
	{
		api.GET("/ping", func(c *gin.Context) {
			c.JSON(http.StatusOK, gin.H{
				"success":     true,
				"message":     "Golang Gin IoT Attendance Server is Running",
				"server_time": time.Now().Format(time.RFC3339),
			})
		})

		protected := api.Group("/")
		protected.Use(authMiddleware)
		{
			protected.POST("/scan", func(c *gin.Context) {
				var req ScanRequest
				if err := c.ShouldBindJSON(&req); err != nil {
					c.JSON(http.StatusBadRequest, gin.H{"success": false, "message": "Field 'code' is required"})
					return
				}

				// Mock scan logic
				now := time.Now()
				jam := now.Format("15:04")

				c.JSON(http.StatusOK, gin.H{
					"success": true,
					"status":  "Hadir",
					"nama":    "Ahmad Fauzi",
					"kelas":   "XII RPL 1",
					"jam":     jam,
					"message": "Absensi Masuk Berhasil",
				})
			})

			protected.POST("/heartbeat", func(c *gin.Context) {
				c.JSON(http.StatusOK, gin.H{
					"success":            true,
					"server_time":        time.Now().Format(time.RFC3339),
					"heartbeat_interval": 60,
					"message":            "Heartbeat OK",
				})
			})
		}
	}

	port := os.Getenv("PORT")
	if port == "" {
		port = "8080"
	}
	r.Run(":" + port)
}
