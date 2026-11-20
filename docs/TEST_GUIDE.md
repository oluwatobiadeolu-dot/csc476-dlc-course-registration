# Test guide (TC01 to TC17)

Run each test, then fill in Actual result, Pass/Fail and Open problem.

Setup: import `database/schema.sql`. Import `database/sample_data.sql` before TC16 and TC17
(it adds 9 sample students, 9 courses, 12 registrations and sample deadlines).

Sample logins: admin `admin@dlc.test` / `Admin@12345`. Any sample student, for example `ada@dlc.test` / `Student@123`
(Computer Science, 100 Level, First semester courses CSC 111 and CSC 113).

| TC | Steps | Expected result | Actual result | Pass/Fail | Open problem |
|----|-------|-----------------|---------------|-----------|--------------|
| TC01 | Create account with valid details on the sign-up page. | Account is created successfully | | | |
| TC02 | Log in as that student. | Student is taken to the student dashboard | | | |
| TC03 | Log in as the admin. | Administrator is taken to the admin dashboard | | | |
| TC04 | Admin: Courses > Add course (Computer Science, 100 Level, First), save as draft. Log in as a 100 Level CS student and open Courses. | The course is not visible or available to students | | | |
| TC05 | Admin: Review the draft and click Publish. Student refreshes Courses. | The course becomes available to eligible students | | | |
| TC06 | Student searches for the course code, ticks it and clicks Add selected to cart. | The selected course appears in the registration cart | | | |
| TC07 | Student clicks Register selected courses, then opens the registration slip. | Courses are saved and the slip displays the registered courses | | | |
| TC08 | Student returns to Courses and tries to add the same course again. | The system prevents duplicate registration (Registered label, no Add button) | | | |
| TC09 | Admin: Deadlines, set future deadlines. Student opens the dashboard. | The banner displays the correct deadline information | | | |
| TC10 | Student adds a course to the cart. Admin sets the registration deadline to a past time. Student clicks Register selected courses. | The server rejects the registration request | | | |
| TC11 | Student: Courses > Report issue, choose a course, submit. Admin opens Reports. | The report appears in the administrator's report list | | | |
| TC12 | Admin opens the report, sets the status to Under review, then to Resolved with a response, saving each time. Student opens My reports after each save. | The student can view the updated status or response | | | |
| TC13 | Student: My registrations > Drop, then click Cancel. | The course remains in the student's active registrations | | | |
| TC14 | Student: Drop a course, click Confirm drop (drop deadline in the future). | The course is removed from active registrations and its status is updated | | | |
| TC15 | Student opens the Drop confirmation page. Admin sets the drop deadline to a past time. Student clicks Confirm drop. | The server rejects the drop request | | | |
| TC16 | Import sample_data.sql. Admin opens the dashboard (current session). | Counts by course and by level match the sample data list at the top of sample_data.sql | | | |
| TC17 | Admin looks at the "Students with no registrations" list. | Only Hassan Yusuf and Ife Ojo appear | | | |

## Optional extension tests (compulsory/elective labels, unit calculator, unit limits)

Use a 100 Level Computer Science student with no registrations (for example `ife@dlc.test`). Courses must be published.

| ID | Steps | Expected result | Actual result | Pass/Fail | Open problem |
|----|-------|-----------------|---------------|-----------|--------------|
| OPT1 | Admin adds one compulsory and one elective course and publishes both. Student opens Courses. | Each course shows a Compulsory or Elective label; compulsory courses are listed first | | | |
| OPT2 | Student ticks courses and watches the panel at the bottom. | Selected units and total units change immediately as boxes are ticked or cleared | | | |
| OPT3 | Student clicks "Select all compulsory". | All compulsory courses are ticked and the total updates | | | |
| OPT4 | Admin: Unit limits, set maximum 5. Student ticks courses worth more than 5 units. | The panel shows it is above the maximum and the Add button is disabled | | | |
| OPT5 | With maximum 5, student adds courses worth 3 units, then tries to add another 3 units. | The server refuses the second addition with a message about the maximum | | | |
| OPT6 | Admin sets maximum 24 and minimum 15. Student registers courses totalling fewer than 15 units. | Registration succeeds and a below-minimum warning is shown | | | |
| OPT7 | Admin enters minimum 20 and maximum 10, then a non-number. | The form rejects both and keeps the old limits | | | |
| OPT8 | Student opens the registration slip and My registrations. | The course type is shown for each course | | | |

## Carryover courses

| ID | Steps | Expected result | Actual result | Pass/Fail | Open problem |
|----|-------|-----------------|---------------|-----------|--------------|
| CO1 | Log in as a 400 Level student. Open Courses (default list). | Only 400 Level courses are listed | | | |
| CO2 | Click "All levels up to mine", or search for a 200 Level course code. | Lower-level courses appear with a Carryover label | | | |
| CO3 | Tick a carryover course, add it to the cart and register it. | It is registered and shows on My registrations and the slip with its level | | | |
| CO4 | Log in as a 100 Level student and look for 400 Level courses. | Higher-level courses never appear | | | |

Notes:
- Courses shown to a student must match the student's level, department and the current semester.
- A deadline that has not been set does not block anything. Set both deadlines before testing TC09, TC10 and TC15.
- Unit limits are configurable placeholders (15 and 24 at first). They are not official University policy unless verified.
- Carryovers are limited to the student's own department, the current semester, and levels up to the student's level.
