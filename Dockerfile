# Use official Java image
FROM openjdk:17-jdk-slim

# Set working directory
WORKDIR /app

# Copy jar file from target folder
COPY target/simple-java-app-1.0.jar app.jar

# Run the application
CMD ["java", "-jar", "app.jar"]