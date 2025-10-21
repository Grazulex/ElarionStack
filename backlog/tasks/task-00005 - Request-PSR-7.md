---
id: task-00005
title: Request PSR-7
status: In Progress
assignee:
  - '@ai-assistant'
created_date: '2025-10-21 19:57'
updated_date: '2025-10-21 20:59'
labels:
  - http
  - psr-7
dependencies: []
priority: high
---

## Description

<!-- SECTION:DESCRIPTION:BEGIN -->
Implémenter une classe ServerRequest conforme PSR-7 pour représenter les requêtes HTTP entrantes de manière immutable et standardisée.
<!-- SECTION:DESCRIPTION:END -->

## Acceptance Criteria
<!-- AC:BEGIN -->
- [x] #1 La classe implémente PSR-7 ServerRequestInterface
- [x] #2 On peut accéder aux query parameters, body, headers
- [x] #3 On peut accéder aux cookies et uploaded files
- [x] #4 Les objets Request sont immutables
- [x] #5 La classe peut être créée depuis les superglobales PHP
- [x] #6 Les tests vérifient la conformité PSR-7
<!-- AC:END -->

## Implementation Plan

<!-- SECTION:PLAN:BEGIN -->
## Phase 1: Core PSR-7 Implementations

1. Create Uri class (PSR-7 UriInterface)
   - Parse and build URIs
   - Immutable with withX() methods
   - Scheme, host, port, path, query, fragment

2. Create Stream class (PSR-7 StreamInterface)
   - Wraps PHP streams (php://input, php://memory, files)
   - Read/write/seek operations
   - Detach and close

3. Create UploadedFile class (PSR-7 UploadedFileInterface)
   - Represents uploaded files
   - Move to target location
   - Error handling

## Phase 2: Message Base Classes

4. Create Message abstract class
   - Base for Request and Response
   - Headers management
   - Protocol version
   - Body (Stream)

## Phase 3: Request Implementation

5. Create Request class (PSR-7 RequestInterface)
   - HTTP method
   - Request target
   - Uri
   - Extends Message

6. Create ServerRequest class (PSR-7 ServerRequestInterface)
   - Server params ($_SERVER)
   - Query params ($_GET)
   - Parsed body ($_POST)
   - Cookies ($_COOKIE)
   - Uploaded files ($_FILES)
   - Attributes (route params)
   - Extends Request

## Phase 4: PSR-17 Factories

7. Create ServerRequestFactory (PSR-17)
   - createServerRequest()
   - createServerRequestFromGlobals()

8. Create UriFactory (PSR-17)
   - createUri()
   - createUriFromString()

9. Create StreamFactory (PSR-17)
   - createStream()
   - createStreamFromFile()
   - createStreamFromResource()

10. Create UploadedFileFactory (PSR-17)
    - createUploadedFile()

## Phase 5: Helper Classes

11. Create HeaderBag
    - Case-insensitive header storage
    - Multi-value headers

12. Create ParameterBag
    - Type-safe parameter access
    - get(), has(), all()

## Phase 6: Comprehensive Testing

13. Unit tests for each component
    - Uri parsing and building
    - Stream operations
    - UploadedFile handling
    - ServerRequest from globals
    - Immutability tests

14. PSR-7 compliance tests
    - Use http-interop/http-factory-tests
    - Verify all PSR-7 requirements
<!-- SECTION:PLAN:END -->
