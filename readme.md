# **Debts Management Application**  
### **Project Overview**  
This project aims to develop a web-based application using **PHP** as the backend stack and **MySQL** as the database. The application will allow users to enter debts for their daily spending and upload supporting images. It will feature responsive mobile-first design using **Tailwind CSS**.

---

## **Technology Stack**  
- **Backend**: PHP  
- **Database**: MySQL  
- **Frontend**: HTML, Tailwind CSS (Mobile Layout Design Template)  
- **Image Upload**: PHP File Handling  
- **QR Code**: QR Generation Library (e.g., [PHP QR Code](https://sourceforge.net/projects/phpqrcode/))  
- **config.php*** - store all database credentials in config.php

---

## **Application Features**  

### **1. Data Entry Page**  
- **Login Page**: Admin-only login (no logout time limit).  
- **Functionality**:  
    - Admin verifies the user by entering the `Member ID`.  
    - System searches the database and displays the user's **Full Name**.  
    - Admin confirms the name to proceed.  
    - **Data Entry Form**:  
        - Fields:  
            - **Amount**  
            - **Description**  
            - **Upload Image** (via file input using phone camera or file selection).  
        - On submission:  
            - Transaction data is stored in the database:  
                - User ID, Amount, Description, Image, Date, Time, etc.  
            - System returns to the main entry page.  
- **Generate QR Code**:  
    - A QR Code will be generated and displayed.  
    - This allows users to scan the code for accessing the data entry functionality via their dashboard.  

---

### **2. User Dashboard**  
- **Login**: User logs into their account to access personalized features.  
- **Dashboard Features**:  
    - **View Account Summary**:  
        - Total Outstanding Debts  
        - Current Month's Spending  
    - **Data Entry**:  
        - Scan the QR Code (using phone camera) to perform new data entry.  
        - Fields: Amount, Description, and Upload Image.  
    - **Transaction History**:  
        - Display a list of past transactions with details:  
            - **Date**, **Time**, **Description**, and **Image**.  

---

### **3. Admin Dashboard**  
- **Login**: Admin-only access.  
- **Dashboard Features**:  
    - **Overview**:  
        - Total Number of Users  
        - Total Outstanding Balance (all users)  
        - Total Payment Received (Last Month)  
    - **Outstanding Debts**:  
        - List of Users with Outstanding Debts and Amounts.  
    - **User Management**:  
        - Create User: Options for **Admin** or **Customer** roles.  
    - **Payment Page**:  
        - Admin enters a **Customer ID** to search user records.  
        - System displays:  
            - User’s Total Outstanding Balance  
            - Total Payments Made Earlier  
            - Transaction History  
        - Admin enters the **Payment Amount**, and the system updates:  
            - Outstanding Balance.  
            - Stores the payment record.  





## **System Flow**  

### **Data Entry Process**  
1. Admin login.  
2. Admin enters the **Member ID** to search and confirm the user.  
3. User enters:  
   - Amount  
   - Description  
   - Upload Image  
4. Data is stored in the **Transactions Table**.  

### **User Dashboard**  
1. User login.  
2. User can:  
   - View their outstanding balance and spending.  
   - Perform data entry via QR Code Scan.  
   - Review transaction history.  

### **Admin Dashboard**  
1. Admin login.  
2. Admin can:  
   - Manage users.  
   - View all financial summaries and outstanding records.  
   - Record payments and update user balances.  

---

## **Responsive Design**  
- Utilize **Tailwind CSS** for a clean, mobile-friendly UI.  
- Implement a responsive layout for all pages:  
    - **Login Page**  
    - **Data Entry Page**  
    - **User Dashboard**  
    - **Admin Dashboard**  


